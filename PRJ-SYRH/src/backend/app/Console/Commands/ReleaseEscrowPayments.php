<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ChatMessage;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Release escrow payments whose release_at has passed.
 * Should run every minute via scheduler.
 *
 * Flow:
 *   1. Find payments with status='paid', notes->type='escrow', release_at <= now
 *   2. Update status to 'released', set released_at
 *   3. Update the payment_request message in chat to show 'released' status
 *   4. Log the release
 *
 * In production, this would also call SAKK API to transfer funds to agency wallet.
 */
class ReleaseEscrowPayments extends Command
{
    protected $signature = 'escrow:release';
    protected $description = 'Release escrow payments past their release date';

    public function handle(): int
    {
        $this->info('Checking for escrow payments to release...');

        // Find payments ready for release
        $now = now()->format('Y-m-d H:i:s');
        $payments = Payment::where('status', 'paid')
            ->where('notes->type', 'escrow')
            ->whereNotNull('notes->release_at')
            ->where('notes->release_at', '<=', $now)
            ->get();

        if ($payments->isEmpty()) {
            $this->info('No escrow payments ready for release.');
            return Command::SUCCESS;
        }

        $released = 0;

        foreach ($payments as $payment) {
            $notes = $payment->notes ?? [];
            $conversationId = $notes['conversation_id'] ?? null;

            DB::beginTransaction();
            try {
                $payment->update([
                    'status'      => 'released',
                    'notes'       => array_merge($notes, [
                        'released_at' => now()->format('Y-m-d H:i:s'),
                    ]),
                ]);

                // Update payment request message in chat
                if ($conversationId) {
                    // Fetch and update in PHP for DB-agnostic approach
                    $chatMsg = ChatMessage::where('conversation_id', $conversationId)
                        ->where('message_type', 'payment_request')
                        ->where('metadata->payment_id', $payment->id)
                        ->first();

                    if ($chatMsg) {
                        $meta = $chatMsg->metadata ?? [];
                        $meta['status'] = 'released';
                        $meta['released_at'] = now()->format('c');
                        $chatMsg->update(['metadata' => $meta]);
                    }
                }

                DB::commit();
                $released++;

                Log::info('Escrow payment released', [
                    'payment_id'      => $payment->id,
                    'amount'          => $payment->amount,
                    'currency'        => $payment->currency,
                    'conversation_id' => $conversationId,
                ]);

                $this->info("Released payment #{$payment->id} ({$payment->amount} {$payment->currency})");
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Failed to release escrow payment', [
                    'payment_id' => $payment->id,
                    'error'      => $e->getMessage(),
                ]);
                $this->error("Failed to release payment #{$payment->id}: {$e->getMessage()}");
            }
        }

        $this->info("Released {$released} escrow payment(s).");
        return Command::SUCCESS;
    }
}
