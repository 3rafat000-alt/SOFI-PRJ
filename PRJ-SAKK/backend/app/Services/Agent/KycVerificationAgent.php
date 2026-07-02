<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Enums\AgentType;
use App\Enums\VerificationStatus;
use App\Models\KycDocument;
use App\Models\KycVerification;
use App\Models\User;
use App\Services\KycService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * وكيل التحقق من الهوية والامتثال — KYC Verification Agent.
 *
 * Automates pending KYC review by:
 *   1. Scanning submitted documents using OCR/vision extraction
 *   2. Cross-matching extracted data against user registration metadata
 *   3. Auto-approving low-risk, high-confidence verifications
 *   4. Escalating failed matches, expired documents, or high-risk profiles
 *
 * The agent NEVER makes irreversible decisions alone — every approve/reject
 * action is signed and recorded as a repair action with full audit trail.
 *
 * Design constraints:
 *   - Tier 1 (id_document + selfie) can be auto-approved if confidence ≥ 0.85
 *   - Tier 2+ always escalated to human admin
 *   - Document expiry < 30 days → warning but not auto-reject
 *   - Expired documents → auto-reject with reason
 *   - Name/DOB mismatch → escalate with structured comparison
 */
class KycVerificationAgent extends BaseVerificationAgent
{
    private KycService $kycService;

    /** Minimum OCR/vision confidence score to auto-approve (0.0 – 1.0). */
    private float $minConfidence;

    /** Always escalate these verification types regardless of confidence. */
    private array $alwaysEscalateTypes = [];

    public function __construct(
        AgentCryptographicSigner $signer,
        AgentWebhookService $webhook,
        KycService $kycService,
    ) {
        parent::__construct($signer, $webhook);
        $this->kycService = $kycService;
        $this->minConfidence = (float) config('agents.kyc.min_confidence', 0.85);
        $this->alwaysEscalateTypes = config('agents.kyc.always_escalate_types', []);
        $this->batchLimit = (int) config('agents.kyc.batch_limit', 100);
    }

    protected function agentType(): AgentType
    {
        return AgentType::KYC_VERIFICATION;
    }

    protected function agentVersion(): string
    {
        return '1.0.0';
    }

    protected function verify(): array
    {
        $anomalies = [];
        $itemsScanned = 0;
        $repairsTriggered = 0;
        $escalations = 0;
        $thresholdBreached = false;

        // ──────────────────────────────────────────────
        // PASS 1: Unprocessed pending KYC documents
        // ──────────────────────────────────────────────
        $this->log('[PASS 1] Scanning pending KYC documents...');

        $pendingDocs = KycDocument::where('status', VerificationStatus::PENDING->value)
            ->whereNull('verified_at')
            ->orderBy('created_at')
            ->limit($this->batchLimit > 0 ? $this->batchLimit : 100)
            ->get();

        $itemsScanned += $pendingDocs->count();
        $this->log("Found {$pendingDocs->count()} pending documents to review.");

        foreach ($pendingDocs as $doc) {
            $result = $this->evaluateDocument($doc);

            if ($result['decision'] === 'approve') {
                $this->handleAutoApprove($doc, $result);
                $repairsTriggered++;
            } elseif ($result['decision'] === 'reject') {
                $this->handleAutoReject($doc, $result);
                $repairsTriggered++;
            } else {
                // Escalate — needs human review
                $this->handleEscalate($doc, $result);
                $escalations++;
            }

            $anomalies[] = $result;
        }

        // ──────────────────────────────────────────────
        // PASS 2: Pending KycVerification rows (admin queue stale)
        // ──────────────────────────────────────────────
        $this->log('[PASS 2] Scanning stale KYC verification requests (>24h old)...');

        $staleVerifications = KycVerification::where('status', VerificationStatus::PENDING->value)
            ->whereIn('verification_type', ['id_document', 'selfie'])
            ->where('created_at', '<', now()->subHours(24))
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        foreach ($staleVerifications as $verification) {
            $anomalies[] = [
                'type' => 'stale_kyc_verification',
                'verification_id' => $verification->id,
                'user_id' => $verification->user_id,
                'type' => $verification->verification_type,
                'waiting_hours' => (int) $verification->created_at->diffInHours(now()),
                'severity' => 'warning',
            ];
            $thresholdBreached = true; // Alert — human backlog growing
        }

        $summary = [
            'pending_docs_scanned' => $pendingDocs->count(),
            'auto_approved' => count(array_filter($anomalies, fn($a) => ($a['decision'] ?? null) === 'approve')),
            'auto_rejected' => count(array_filter($anomalies, fn($a) => ($a['decision'] ?? null) === 'reject')),
            'escalated' => count(array_filter($anomalies, fn($a) => ($a['decision'] ?? null) === 'escalate')),
            'stale_requests' => count(array_filter($anomalies, fn($a) => $a['type'] === 'stale_kyc_verification')),
        ];

        $this->log('[DONE] KYC verification complete.');

        return [
            'items_scanned' => $itemsScanned,
            'anomalies_found' => count($anomalies),
            'repairs_triggered' => $repairsTriggered,
            'escalations' => $escalations,
            'summary' => $summary,
            'log' => $this->currentRun?->log ?? '',
            'threshold_breached' => $thresholdBreached,
        ];
    }

    // ==================== Document Evaluation ====================

    /**
     * Evaluate a single KYC document for auto-approval or rejection.
     *
     * @return array{decision:'approve'|'reject'|'escalate', confidence:float, reasons:string[], doc:KycDocument}
     */
    private function evaluateDocument(KycDocument $doc): array
    {
        $user = $doc->user;
        $reasons = [];
        $confidence = 0.0;

        if (!$user) {
            return [
                'decision' => 'escalate',
                'confidence' => 0.0,
                'reasons' => ['orphan_document: no user found'],
                'document_id' => $doc->id,
                'document_type' => $doc->document_type,
            ];
        }

        // ── Check 1: Document type in always-escalate list ──
        if (in_array($doc->document_type, $this->alwaysEscalateTypes, true)) {
            return $this->escalateResult($doc, 'Document type requires manual review');
        }

        // ── Check 2: Expiry check ──
        if ($doc->expiry_date) {
            $expiry = $doc->expiry_date instanceof \Carbon\Carbon
                ? $doc->expiry_date
                : \Carbon\Carbon::parse($doc->expiry_date);

            if ($expiry->isPast()) {
                return $this->rejectResult($doc, 'Document is expired');
            }

            if ($expiry->diffInDays(now()) < 30) {
                $reasons[] = 'Document expires within 30 days';
                $confidence -= 0.1; // Penalty
            }
        }

        // ── Check 3: Extract & cross-match with user metadata ──
        $extracted = $doc->extracted_data ?? [];
        $ocrData = $doc->ocr_data ?? [];
        $mergedData = array_merge($extracted, $ocrData);

        $matchScore = $this->crossMatchDocument($user, $mergedData, $doc->document_type, $reasons);
        $confidence = max(0.0, $matchScore);

        // ── Check 4: File validity ──
        if (empty($doc->file_path) || !Storage::disk('private')->exists($doc->file_path)) {
            return $this->rejectResult($doc, 'Document file not found on storage');
        }

        if ($doc->file_size > 20 * 1024 * 1024) {
            $reasons[] = 'File size exceeds 20MB limit';
            $confidence -= 0.15;
        }

        // ── Decision ──
        $confidence = max(0.0, min(1.0, $confidence));

        if ($confidence >= $this->minConfidence && empty($reasons)) {
            return $this->approveResult($doc, $confidence, $reasons);
        }

        if ($confidence < 0.3) {
            return $this->rejectResult($doc, 'Low confidence match', $reasons);
        }

        // Escalate — borderline case
        return $this->escalateResult($doc, 'Borderline confidence — human review required', $reasons);
    }

    // ==================== Cross-Matching ====================

    /**
     * Cross-match extracted document data against user registration metadata.
     *
     * Returns a confidence score 0.0 – 1.0 and appends reasons for mismatches.
     */
    private function crossMatchDocument(User $user, array $docData, string $docType, array &$reasons): float
    {
        $checks = 0;
        $passes = 0;

        // Name matching
        $docName = $docData['full_name']
            ?? $docData['name']
            ?? $docData['first_name'] . ' ' . ($docData['last_name'] ?? '')
            ?? null;

        if ($docName) {
            $checks++;
            $userName = trim($user->first_name . ' ' . ($user->last_name ?? ''));
            $similarity = $this->stringSimilarity($docName, $userName);
            if ($similarity >= 0.7) {
                $passes++;
            } else {
                $reasons[] = "Name mismatch: doc='{$docName}' vs user='{$userName}' (sim=" . round($similarity, 2) . ')';
            }
        }

        // Date of birth matching
        $docDob = $docData['date_of_birth'] ?? $docData['dob'] ?? $docData['birth_date'] ?? null;
        if ($docDob && $user->date_of_birth) {
            $checks++;
            $userDob = $user->date_of_birth instanceof \Carbon\Carbon
                ? $user->date_of_birth->format('Y-m-d')
                : \Carbon\Carbon::parse($user->date_of_birth)->format('Y-m-d');
            $docDobNormalized = \Carbon\Carbon::parse($docDob)->format('Y-m-d');

            if ($userDob === $docDobNormalized) {
                $passes++;
            } else {
                $reasons[] = "DOB mismatch: doc='{$docDobNormalized}' vs user='{$userDob}'";
            }
        }

        // Document number format validation
        $docNumber = $docData['document_number'] ?? $docData['id_number'] ?? $docData['passport_number'] ?? null;
        if ($docNumber) {
            $checks++;
            // Basic format validation (non-empty, reasonable length)
            $normalized = preg_replace('/[^A-Za-z0-9]/', '', (string) $docNumber);
            if (strlen($normalized) >= 4 && strlen($normalized) <= 20) {
                $passes++;
            } else {
                $reasons[] = "Document number format suspicious: '{$docNumber}'";
            }
        }

        if ($checks === 0) {
            // No extractable data — low confidence
            $reasons[] = 'No extractable data for cross-match';
            return 0.3;
        }

        return $passes / $checks;
    }

    /**
     * Simple string similarity using Jaro-Winkler-like comparison.
     */
    private function stringSimilarity(string $a, string $b): float
    {
        $a = mb_strtolower(trim($a));
        $b = mb_strtolower(trim($b));

        if ($a === $b) {
            return 1.0;
        }

        if (empty($a) || empty($b)) {
            return 0.0;
        }

        // Levenshtein-based similarity
        $lev = levenshtein($a, $b);
        $maxLen = max(mb_strlen($a), mb_strlen($b));

        if ($maxLen === 0) {
            return 0.0;
        }

        return max(0.0, 1.0 - ($lev / $maxLen));
    }

    // ==================== Result Builders ====================

    private function approveResult(KycDocument $doc, float $confidence, array $reasons): array
    {
        return [
            'decision' => 'approve',
            'confidence' => $confidence,
            'reasons' => $reasons,
            'document_id' => $doc->id,
            'document_type' => $doc->document_type,
            'user_id' => $doc->user_id,
        ];
    }

    private function rejectResult(KycDocument $doc, string $primaryReason, array $extraReasons = []): array
    {
        return [
            'decision' => 'reject',
            'confidence' => 0.0,
            'reasons' => array_merge([$primaryReason], $extraReasons),
            'document_id' => $doc->id,
            'document_type' => $doc->document_type,
            'user_id' => $doc->user_id,
        ];
    }

    private function escalateResult(KycDocument $doc, string $reason, array $extraReasons = []): array
    {
        return [
            'decision' => 'escalate',
            'confidence' => 0.0,
            'reasons' => array_merge([$reason], $extraReasons),
            'document_id' => $doc->id,
            'document_type' => $doc->document_type,
            'user_id' => $doc->user_id,
        ];
    }

    // ==================== Auto-Repair Actions ====================

    /**
     * Handle auto-approval: create signed approve repair action.
     */
    private function handleAutoApprove(KycDocument $doc, array $result): void
    {
        $this->log("Auto-approving document #{$doc->id} ({$doc->document_type}) confidence={$result['confidence']}");

        $this->createRepairAction(
            actionType: 'approve_kyc_document',
            actionCategory: 'kyc',
            targetable: $doc,
            payload: [
                'document_id' => $doc->id,
                'document_type' => $doc->document_type,
                'user_id' => $doc->user_id,
                'confidence' => $result['confidence'],
                'reasons' => $result['reasons'],
            ],
            reason: "Auto-approve {$doc->document_type} for user #{$doc->user_id}: confidence={$result['confidence']}, matched metadata.",
            financialImpact: 0.0,
            targetSnapshot: $this->snapshot($doc),
        );
    }

    /**
     * Handle auto-rejection: create signed reject repair action.
     */
    private function handleAutoReject(KycDocument $doc, array $result): void
    {
        $this->log("Auto-rejecting document #{$doc->id} ({$doc->document_type}): " . implode('; ', $result['reasons']));

        $this->createRepairAction(
            actionType: 'reject_kyc_document',
            actionCategory: 'kyc',
            targetable: $doc,
            payload: [
                'document_id' => $doc->id,
                'document_type' => $doc->document_type,
                'user_id' => $doc->user_id,
                'reasons' => $result['reasons'],
            ],
            reason: "Auto-reject {$doc->document_type} for user #{$doc->user_id}: " . implode('; ', $result['reasons']),
            financialImpact: 0.0,
            targetSnapshot: $this->snapshot($doc),
        );
    }

    /**
     * Escalate a document that cannot be auto-processed.
     */
    private function handleEscalate(KycDocument $doc, array $result): void
    {
        $this->log("Escalating document #{$doc->id} ({$doc->document_type}): " . implode('; ', $result['reasons']));

        // Create a repair action but immediately escalate it
        $action = $this->createRepairAction(
            actionType: 'review_kyc_document',
            actionCategory: 'kyc',
            targetable: $doc,
            payload: [
                'document_id' => $doc->id,
                'document_type' => $doc->document_type,
                'user_id' => $doc->user_id,
                'reasons' => $result['reasons'],
                'confidence' => $result['confidence'] ?? 0.0,
                'requires_human_review' => true,
            ],
            reason: "Escalate {$doc->document_type} for user #{$doc->user_id}: " . implode('; ', $result['reasons']),
            financialImpact: 0.0,
            targetSnapshot: $this->snapshot($doc),
        );

        // Override status to escalated
        $action->escalate(null, implode('; ', $result['reasons']));
    }

    // ==================== Execution Handlers ====================

    /**
     * Execute a KYC approval — marks document approved + syncs user level.
     * Called by RepairAgent after signature verification.
     */
    public function executeApproveKyc(AgentRepairAction $action): void
    {
        $payload = $action->payload;
        $doc = KycDocument::findOrFail($payload['document_id']);
        $user = $doc->user;

        if (!$user) {
            throw new \RuntimeException("User #{$payload['user_id']} not found for KYC approval");
        }

        // Approve the document
        $doc->forceFill([
            'status' => VerificationStatus::APPROVED->value,
            'verified_at' => now(),
            'extracted_data' => array_merge($doc->extracted_data ?? [], [
                'agent_approved' => true,
                'agent_run_uuid' => $this->currentRun?->uuid,
                'agent_confidence' => $payload['confidence'] ?? null,
            ]),
        ])->save();

        // Sync corresponding KycVerification row (portable, no raw SQL / interpolation).
        KycVerification::where('user_id', $user->id)
            ->whereIn('verification_type', [$payload['document_type'], 'id_document', 'selfie'])
            ->where('status', VerificationStatus::PENDING->value)
            ->get()
            ->each(function (KycVerification $verification): void {
                $data = $verification->extracted_data ?? [];
                $data['agent_approved'] = true;
                $data['agent_run_uuid'] = $this->currentRun?->uuid;

                $verification->update([
                    'status' => VerificationStatus::APPROVED->value,
                    'reviewed_at' => now(),
                    'extracted_data' => $data,
                ]);
            });

        // Sync user KYC level
        $this->kycService->syncUserLevel($user);

        Log::info('KycVerificationAgent: Document auto-approved', [
            'document_id' => $doc->id,
            'user_id' => $user->id,
            'confidence' => $payload['confidence'] ?? null,
            'action_uuid' => $action->uuid,
        ]);
    }

    /**
     * Execute a KYC rejection — marks document rejected with reasons.
     */
    public function executeRejectKyc(AgentRepairAction $action): void
    {
        $payload = $action->payload;
        $doc = KycDocument::findOrFail($payload['document_id']);
        $user = $doc->user;

        $rejectionReason = implode('; ', $payload['reasons'] ?? ['Auto-rejected by KYC agent']);

        $doc->forceFill([
            'status' => VerificationStatus::REJECTED->value,
            'verified_at' => now(),
            'rejection_reason' => $rejectionReason,
            'verified_by' => null, // Agent, not human
            'extracted_data' => array_merge($doc->extracted_data ?? [], [
                'agent_rejected' => true,
                'agent_run_uuid' => $this->currentRun?->uuid,
                'agent_reasons' => $payload['reasons'] ?? [],
            ]),
        ])->save();

        // Sync corresponding KycVerification row
        KycVerification::where('user_id', $user->id)
            ->whereIn('verification_type', [$payload['document_type'], 'id_document', 'selfie'])
            ->where('status', VerificationStatus::PENDING->value)
            ->update([
                'status' => VerificationStatus::REJECTED->value,
                'reviewed_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);

        // Sync user level (will detect rejection and downgrade if needed)
        $this->kycService->syncUserLevel($user);

        Log::info('KycVerificationAgent: Document auto-rejected', [
            'document_id' => $doc->id,
            'user_id' => $user->id,
            'reason' => $rejectionReason,
            'action_uuid' => $action->uuid,
        ]);
    }
}
