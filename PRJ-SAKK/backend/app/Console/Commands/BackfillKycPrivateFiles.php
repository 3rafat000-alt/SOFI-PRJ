<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AgentDocument;
use App\Models\MerchantDocument;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * One-shot remediation: relocate legacy partner (agent/merchant) KYC documents
 * off the PUBLIC storage disk and onto the PRIVATE disk.
 *
 * Background / why this exists
 * ----------------------------
 * Identity documents must never sit on the publicly served disk
 * (storage/app/public, exposed through the /storage symlink + asset()). The
 * current upload path — API\PartnerApplicationController::uploadDocument — was
 * historically written with `->store('partner-documents', 'public')`, so legacy
 * rows in `agent_documents` / `merchant_documents` may point at public-disk
 * files that are world-readable (a Lirat-class exposure). User-level KYC docs
 * (KycService, key prefix "kyc/{userId}/...") already use the private disk and
 * are NOT in scope here. Avatars ("avatars/..."), card-imports and DB backups
 * are explicitly out of scope and are defended against by a prefix allow-check.
 *
 * What this command does
 * ----------------------
 * For every AgentDocument and MerchantDocument whose `file_path` still lives on
 * the public disk under the "partner-documents/" prefix, it copies the bytes to
 * the private disk at the SAME relative key, verifies the private copy, then
 * deletes the public original. Because the destination disk ('private') and the
 * read disk used by Admin\SecureFileController ('local') share the same root
 * (storage/app/private) and SecureFileController already allow-lists the
 * "partner-documents/" prefix, the document becomes immediately serveable
 * through the gated egress with no path rewrite required.
 *
 * Disk-agnostic key: `file_path` stores a RELATIVE key (e.g.
 * "partner-documents/AbC123.pdf"), not a disk-qualified URL. The same key is
 * valid on both disks, so the only change needed is the physical byte location —
 * there is NO column value to mutate. The command therefore does not write the
 * model back when the key is unchanged; its job reduces to relocating bytes
 * idempotently and removing the public copy.
 *
 * Cross-disk move: Laravel's FilesystemAdapter::move()/copy() operate within a
 * single disk only and cannot move public -> private. We stream the bytes
 * manually: readStream('public') -> writeStream('private') -> verify exists on
 * private -> delete from 'public'. This ordering guarantees the private copy is
 * durably written (and the DB pointer, which is unchanged, already references
 * the shared key) before the public original is removed, so a crash mid-run can
 * never destroy the only copy.
 *
 * Safety contract
 * ---------------
 *  - DEFAULT is a DRY RUN: with no flags (or with --dry-run) it only reports what
 *    WOULD move and mutates nothing — no Storage writes, no Storage deletes, no
 *    model saves.
 *  - A real run requires BOTH --force AND an interactive confirmation. --force
 *    alone (no confirm) aborts. --dry-run always wins if both are passed.
 *  - Idempotent: re-running after a successful run moves 0 files and mutates
 *    nothing. Per-file failures are isolated (logged + counted, loop continues);
 *    the public original is never deleted unless the private copy is verified.
 *
 * Internal operator tooling -> English output (per project convention; Arabic is
 * reserved for end-user-facing UI strings).
 */
class BackfillKycPrivateFiles extends Command
{
    protected $signature = 'kyc:backfill-private
        {--dry-run : Report what would move, mutate nothing (this is also the default)}
        {--force : Actually move files on disk and rewrite storage location (destructive: deletes public copies)}';

    protected $description = 'Move legacy public-disk partner (agent/merchant) KYC documents to the private disk (idempotent, safe; dry-run by default).';

    /**
     * Only keys under this prefix are ever touched. Anything else (avatars/,
     * card-imports/, ...) is skipped defensively so this command can never
     * relocate a non-KYC file.
     */
    private const PARTNER_PREFIX = 'partner-documents/';

    /** @var array<string,int> */
    private array $counters = [
        'moved' => 0,        // bytes copied public -> private, public original deleted
        'reconciled' => 0,   // already on private but a stale public dup existed -> public dup deleted
        'skipped_private' => 0,   // already private (or empty path) -> nothing to do
        'skipped_foreign' => 0,   // path outside partner-documents/ prefix -> never touched
        'orphaned' => 0,     // path missing on BOTH disks -> warn, do nothing
        'failed' => 0,       // per-file error (copy/verify/delete) -> logged, loop continues
    ];

    /** Rows collected for the dry-run report table. @var array<int,array<int,string>> */
    private array $reportRows = [];

    public function handle(): int
    {
        // --dry-run wins outright; otherwise a real run needs --force.
        $dryRun = $this->option('dry-run') || ! $this->option('force');

        if ($dryRun) {
            $this->info('DRY RUN — no files will be moved and the database will not be changed.');
        } else {
            // Real, destructive run: --force present and not overridden by --dry-run.
            $this->warn('This will MOVE partner KYC documents from the PUBLIC disk to the PRIVATE disk and DELETE the public copies.');
            if (! $this->confirm('Proceed with the real move?')) {
                $this->warn('Aborted — nothing was changed.');

                return self::SUCCESS;
            }
        }

        // withTrashed(): soft-deleted partner documents must also be relocated —
        // a soft-deleted row still leaves its bytes on the public disk, which is
        // exactly the exposure we are closing. (Judgment call: prefer relocating
        // them over leaving orphaned public files behind.)
        $this->processModel(
            AgentDocument::query()->withTrashed(),
            'AgentDocument',
            $dryRun,
        );
        $this->processModel(
            MerchantDocument::query()->withTrashed(),
            'MerchantDocument',
            $dryRun,
        );

        return $this->report($dryRun);
    }

    /**
     * Stream every candidate row of one model through the classify/move pipeline.
     *
     * chunkById(100) keeps memory flat for large tables and is safe to combine
     * with row mutation (it pages by ascending id, not by offset).
     */
    private function processModel(Builder $query, string $label, bool $dryRun): void
    {
        $query->whereNotNull('file_path')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($label, $dryRun): void {
                foreach ($rows as $doc) {
                    $this->processRow($doc, $label, $dryRun);
                }
            });
    }

    /**
     * Classify a single row and (unless dry-run) perform the relocation.
     *
     * Classification precedence:
     *   1. empty path                       -> skipped_private
     *   2. not under partner-documents/      -> skipped_foreign (never touched)
     *   3. exists on public                  -> MOVE (or, if also on private, RECONCILE)
     *   4. exists on private only            -> skipped_private (already migrated)
     *   5. missing on both disks             -> orphaned (warn, do nothing)
     */
    private function processRow(Model $doc, string $label, bool $dryRun): void
    {
        /** @var string $path */
        $path = (string) $doc->getAttribute('file_path');
        $id = (string) $doc->getKey();

        if ($path === '') {
            $this->counters['skipped_private']++;

            return;
        }

        // Defensive: never relocate a key outside the partner-documents/ tree.
        if (! str_starts_with($path, self::PARTNER_PREFIX)) {
            $this->counters['skipped_foreign']++;
            $this->addReportRow($label, $id, $path, 'skip (foreign prefix)');

            return;
        }

        $public = Storage::disk('public');
        $private = Storage::disk('private');

        $onPublic = $public->exists($path);
        $onPrivate = $private->exists($path);

        // Already fully migrated: only on the private disk -> nothing to do.
        if (! $onPublic && $onPrivate) {
            $this->counters['skipped_private']++;

            return;
        }

        // Orphan: the row points at a file that exists nowhere. Surface it but do
        // not invent data — this needs a human to investigate.
        if (! $onPublic && ! $onPrivate) {
            $this->counters['orphaned']++;
            $this->addReportRow($label, $id, $path, 'ORPHAN (missing on both disks)');
            $this->warn("[$label #$id] orphaned file_path (missing on both public and private disks): $path");

            return;
        }

        // From here on the file exists on the public disk and must move.
        // RECONCILE: a prior interrupted run already wrote the private copy but
        // left the public dup behind — just drop the public dup.
        if ($onPublic && $onPrivate) {
            if ($dryRun) {
                $this->counters['reconciled']++;
                $this->addReportRow($label, $id, $path, 'would reconcile (delete public dup; private copy exists)');

                return;
            }

            $this->reconcileDuplicate($public, $label, $id, $path);

            return;
        }

        // MOVE: file is only on the public disk.
        if ($dryRun) {
            $this->counters['moved']++;
            $this->addReportRow($label, $id, $path, 'would move public -> private');

            return;
        }

        $this->movePublicToPrivate($public, $private, $label, $id, $path);
    }

    /**
     * Stream the bytes public -> private, verify the private copy, then delete the
     * public original. Any failure is isolated: it is logged, counted, and the
     * loop continues. The public original is deleted ONLY after the private copy
     * is confirmed to exist, so the file can never be lost.
     */
    private function movePublicToPrivate(
        \Illuminate\Contracts\Filesystem\Filesystem $public,
        \Illuminate\Contracts\Filesystem\Filesystem $private,
        string $label,
        string $id,
        string $path,
    ): void {
        $stream = null;

        try {
            $stream = $public->readStream($path);
            if ($stream === null || $stream === false) {
                throw new \RuntimeException('readStream returned no resource for the public source.');
            }

            // writeStream returns false on failure (disk is configured throw=false).
            $written = $private->writeStream($path, $stream);
            if ($written === false) {
                throw new \RuntimeException('writeStream to the private disk failed.');
            }

            // Verify the destination is durably present BEFORE deleting the source.
            if (! $private->exists($path)) {
                throw new \RuntimeException('private copy missing after write — refusing to delete public original.');
            }

            // file_path is a disk-agnostic relative key identical on both disks,
            // so there is no column value to rewrite; the model is intentionally
            // not saved here.

            // Safe to remove the now-redundant public original.
            if (! $public->delete($path)) {
                // Bytes are safe on private; flag the leftover public copy so an
                // operator can clean it, but do not count this as a hard failure.
                $this->warn("[$label #$id] copied to private but failed to delete the public original: $path");
            }

            $this->counters['moved']++;
        } catch (Throwable $e) {
            $this->counters['failed']++;
            $this->error("[$label #$id] move failed for '$path': " . $e->getMessage());
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * Reconcile a partial prior run: the private copy already exists, so only the
     * stale public duplicate needs removing. No DB change (key is unchanged).
     */
    private function reconcileDuplicate(
        \Illuminate\Contracts\Filesystem\Filesystem $public,
        string $label,
        string $id,
        string $path,
    ): void {
        try {
            if ($public->delete($path)) {
                $this->counters['reconciled']++;
            } else {
                $this->counters['failed']++;
                $this->error("[$label #$id] reconcile failed — could not delete public dup: $path");
            }
        } catch (Throwable $e) {
            $this->counters['failed']++;
            $this->error("[$label #$id] reconcile error for '$path': " . $e->getMessage());
        }
    }

    private function addReportRow(string $label, string $id, string $path, string $action): void
    {
        $this->reportRows[] = [$label, $id, $path, $action];
    }

    /**
     * Emit the dry-run table (if any) and the always-on summary, then map the
     * outcome to an exit code: FAILURE when any per-file failure occurred so the
     * caller / CI notices, SUCCESS otherwise.
     */
    private function report(bool $dryRun): int
    {
        if ($dryRun && $this->reportRows !== []) {
            $this->newLine();
            $this->table(['Model', 'ID', 'Path', 'Action'], $this->reportRows);
        }

        $this->newLine();
        $this->info($dryRun ? 'Dry-run summary (nothing was changed):' : 'Run summary:');
        $this->table(
            ['Outcome', 'Count'],
            [
                [$dryRun ? 'would move (public -> private)' : 'moved (public -> private)', (string) $this->counters['moved']],
                [$dryRun ? 'would reconcile (delete public dup)' : 'reconciled (deleted public dup)', (string) $this->counters['reconciled']],
                ['skipped (already private / empty)', (string) $this->counters['skipped_private']],
                ['skipped (foreign prefix, untouched)', (string) $this->counters['skipped_foreign']],
                ['orphaned (missing on both disks)', (string) $this->counters['orphaned']],
                ['failed', (string) $this->counters['failed']],
            ],
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('No changes were made. Re-run with --force (you will be asked to confirm) to perform the move.');

            return self::SUCCESS;
        }

        if ($this->counters['failed'] > 0) {
            $this->error("Completed with {$this->counters['failed']} failure(s) — see the errors above.");

            return self::FAILURE;
        }

        $this->info('Backfill complete. Partner KYC documents are now on the private disk.');

        return self::SUCCESS;
    }
}
