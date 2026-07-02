<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminMiddleware;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    private string $backupDisk = 'local';
    private string $backupDir = 'backups';

    /** Filenames we will ever read/write: timestamped backups only. */
    private const ALLOWED_EXTENSIONS = ['sql', 'sqlite', 'gz'];

    /**
     * Resolve a user-supplied backup filename to a safe relative path, or null.
     *
     * Rejects any traversal/separator attempt ('..' or '/' or '\'), strips to a
     * bare basename, and whitelists to the backups dir + an allowed extension.
     * Returns the relative storage path on success, null on any violation.
     */
    private function resolveSafePath(string $filename): ?string
    {
        // Hard reject traversal and path separators before any normalisation.
        if (str_contains($filename, '..')
            || str_contains($filename, '/')
            || str_contains($filename, '\\')) {
            return null;
        }

        $base = basename($filename);

        // basename() of a sneaky input could still differ; require an exact match.
        if ($base === '' || $base !== $filename) {
            return null;
        }

        $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        return $this->backupDir . '/' . $base;
    }

    public function index(): View
    {
        $files = collect(Storage::disk($this->backupDisk)->files($this->backupDir))
            ->filter(fn ($f) => str_ends_with($f, '.sql') || str_ends_with($f, '.sqlite') || str_ends_with($f, '.gz'))
            ->map(fn ($f) => [
                'filename' => basename($f),
                'path' => $f,
                'size' => Storage::disk($this->backupDisk)->size($f),
                'size_formatted' => $this->formatBytes(Storage::disk($this->backupDisk)->size($f)),
                'date' => Storage::disk($this->backupDisk)->lastModified($f),
                'date_formatted' => date('Y-m-d H:i:s', Storage::disk($this->backupDisk)->lastModified($f)),
            ])
            ->sortByDesc('date')
            ->values();

        $dbConnection = config('database.default');
        $dbSize = $this->getDatabaseSize();

        return view('admin.system.backup', compact('files', 'dbConnection', 'dbSize'));
    }

    public function create(Request $request): RedirectResponse
    {
        $dbConnection = config('database.default');
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "{$timestamp}";

        try {
            if ($dbConnection === 'sqlite') {
                $dbPath = config('database.connections.sqlite.database');
                if (!file_exists($dbPath)) {
                    return back()->with('error', 'ملف قاعدة البيانات غير موجود.');
                }
                $filename .= '.sqlite';
                $dest = $this->backupDir . '/' . $filename;
                Storage::disk($this->backupDisk)->put($dest, file_get_contents($dbPath));
            } elseif ($dbConnection === 'mysql') {
                $filename .= '.sql';
                $dest = $this->backupDir . '/' . $filename;
                $tempPath = Storage::disk($this->backupDisk)->path($dest);

                try {
                    $pdo = DB::connection()->getPdo();
                    $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

                    $sql = "-- CARDA Database Backup\n-- Date: " . now()->toDateTimeString() . "\n\n";
                    foreach ($tables as $table) {
                        $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                        $sql .= "-- Table: {$table}\n";
                        $sql .= $create['Create Table'] . ";\n\n";

                        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
                        foreach ($rows as $row) {
                            $cols = array_map(fn($v) => is_null($v) ? 'NULL' : $pdo->quote($v), $row);
                            $sql .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $cols) . ");\n";
                        }
                        $sql .= "\n";
                    }

                    Storage::disk($this->backupDisk)->put($dest, $sql);
                } catch (\Exception $e) {
                    Storage::disk($this->backupDisk)->delete($dest);
                    Log::error('DB backup failed', ['error' => $e->getMessage()]);
                    return back()->with('error', 'فشل إنشاء النسخة الاحتياطية.');
                }
            } else {
                return back()->with('error', 'قاعدة البيانات غير مدعومة للنسخ الاحتياطي.');
            }

            return back()->with('success', "تم إنشاء النسخة الاحتياطية: {$filename}");
        } catch (\Exception $e) {
            Log::error('DB backup create error', ['error' => $e->getMessage()]);
            return back()->with('error', 'حدث خطأ أثناء إنشاء النسخة الاحتياطية.');
        }
    }

    public function download(string $filename): BinaryFileResponse
    {
        $path = $this->resolveSafePath($filename);

        if ($path === null || !Storage::disk($this->backupDisk)->exists($path)) {
            abort(404, 'ملف النسخة الاحتياطية غير موجود.');
        }

        $fullPath = Storage::disk($this->backupDisk)->path($path);

        return response()->download($fullPath, basename($path));
    }

    public function delete(Request $request, string $filename): RedirectResponse
    {
        AdminMiddleware::authorize('db.delete');

        $path = $this->resolveSafePath($filename);

        if ($path === null) {
            return back()->with('error', 'اسم ملف غير صالح.');
        }

        $name = basename($path);

        // Explicit confirm token: the request must echo back the exact name.
        if ((string) $request->input('confirm_name') !== $name) {
            return back()->with('error', 'يجب تأكيد اسم النسخة الاحتياطية قبل الحذف.');
        }

        if (!Storage::disk($this->backupDisk)->exists($path)) {
            return back()->with('error', 'ملف النسخة الاحتياطية غير موجود.');
        }

        Storage::disk($this->backupDisk)->delete($path);

        ActivityLog::log(
            action: 'admin.db.delete',
            description: 'حذف نسخة احتياطية لقاعدة البيانات: ' . $name,
            newValues: ['file' => $name],
        );

        return back()->with('success', "تم حذف النسخة الاحتياطية: {$name}");
    }

    public function restore(Request $request, string $filename): RedirectResponse
    {
        AdminMiddleware::authorize('db.restore');

        $path = $this->resolveSafePath($filename);

        if ($path === null) {
            return back()->with('error', 'اسم ملف غير صالح.');
        }

        $name = basename($path);

        // Explicit confirm token: the request must echo back the exact name.
        if ((string) $request->input('confirm_name') !== $name) {
            return back()->with('error', 'يجب تأكيد اسم النسخة الاحتياطية قبل الاستعادة.');
        }

        if (!Storage::disk($this->backupDisk)->exists($path)) {
            return back()->with('error', 'ملف النسخة الاحتياطية غير موجود.');
        }

        $dbConnection = config('database.default');

        try {
            if ($dbConnection === 'sqlite') {
                $dbPath = config('database.connections.sqlite.database');

                // Backup the current DB before restoring (safety net)
                $safetyNet = $this->backupDir . '/pre-restore_' . now()->format('Y-m-d_H-i-s') . '.sqlite';
                Storage::disk($this->backupDisk)->put($safetyNet, file_get_contents($dbPath));

                // Close all connections before overwriting
                DB::disconnect();

                $backupContent = Storage::disk($this->backupDisk)->get($path);
                file_put_contents($dbPath, $backupContent);

                ActivityLog::log(
                    action: 'admin.db.restore',
                    description: 'استعادة قاعدة البيانات من النسخة: ' . $name,
                    newValues: ['file' => $name, 'driver' => 'sqlite', 'safety_net' => basename($safetyNet)],
                );

                return back()->with('success', "تمت استعادة النسخة الاحتياطية بنجاح. تم حفظ نسخة من قاعدة البيانات الحالية كاحتياط.");
            } elseif ($dbConnection === 'mysql') {
                // Parity with sqlite: snapshot the current DB before replaying.
                $safetyNet = $this->backupDir . '/pre-restore_' . now()->format('Y-m-d_H-i-s') . '.sql';
                $dumped = $this->dumpMysqlTo($safetyNet);
                if (!$dumped) {
                    return back()->with('error', 'تعذّر إنشاء نسخة أمان قبل الاستعادة. تم إلغاء العملية.');
                }

                $sql = Storage::disk($this->backupDisk)->get($path);

                // Execute the whole dump as multi-statements in one PDO call.
                // Splitting on ";\n" corrupts INSERTs whose VALUES contain it;
                // PDO::exec handles MySQL multi-statements natively.
                DB::beginTransaction();
                try {
                    DB::getPdo()->exec($sql);
                    DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    Log::error('DB restore failed', ['error' => $e->getMessage()]);
                    return back()->with('error', 'فشلت استعادة قاعدة البيانات.');
                }

                ActivityLog::log(
                    action: 'admin.db.restore',
                    description: 'استعادة قاعدة البيانات من النسخة: ' . $name,
                    newValues: ['file' => $name, 'driver' => 'mysql', 'safety_net' => basename($safetyNet)],
                );

                return back()->with('success', 'تمت استعادة قاعدة البيانات من النسخة الاحتياطية بنجاح.');
            } else {
                return back()->with('error', 'قاعدة البيانات غير مدعومة للاستعادة.');
            }
        } catch (\Throwable $e) {
            Log::error('DB restore error', ['error' => $e->getMessage()]);
            return back()->with('error', 'حدث خطأ أثناء استعادة النسخة الاحتياطية.');
        }
    }

    /**
     * Write a logical dump of the current MySQL DB to a backups-relative path.
     * Mirrors create()'s dump format. Returns false on any failure.
     */
    private function dumpMysqlTo(string $relativePath): bool
    {
        try {
            $pdo = DB::connection()->getPdo();
            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

            $sql = "-- CARDA Database Backup\n-- Date: " . now()->toDateTimeString() . "\n\n";
            foreach ($tables as $table) {
                $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                $sql .= "-- Table: {$table}\n";
                $sql .= $create['Create Table'] . ";\n\n";

                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $cols = array_map(fn ($v) => is_null($v) ? 'NULL' : $pdo->quote($v), $row);
                    $sql .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $cols) . ");\n";
                }
                $sql .= "\n";
            }

            Storage::disk($this->backupDisk)->put($relativePath, $sql);

            return true;
        } catch (\Throwable $e) {
            Log::error('Pre-restore MySQL safety dump failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function getDatabaseSize(): string
    {
        $dbConnection = config('database.default');

        if ($dbConnection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (file_exists($dbPath)) {
                return $this->formatBytes(filesize($dbPath));
            }
        } elseif ($dbConnection === 'mysql') {
            try {
                $db = config('database.connections.mysql.database');
                $result = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = ?", [$db]);
                if (!empty($result)) {
                    return $result[0]->size_mb . ' MB';
                }
            } catch (\Exception $e) {
                return 'غير معروف';
            }
        }

        return 'غير معروف';
    }
}
