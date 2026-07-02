<?php

namespace App\Jobs;

use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Services\PayrollService;
use Illuminate\Bus\Batch;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Throwable;

/**
 * Fan a large payroll batch out across the queue, one PayEmployeeJob per pending
 * item, then finalize the batch rollups regardless of individual failures.
 * The gate + funding preflight already ran synchronously in
 * PayrollService::dispatchBatch before this job was queued.
 */
class ProcessPayrollBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public int $batchId) {}

    public function handle(PayrollService $service): void
    {
        $batch = PayrollBatch::find($this->batchId);
        if (!$batch) {
            return;
        }

        $itemIds = $batch->items()
            ->where('status', PayrollItem::STATUS_PENDING)
            ->pluck('id');

        if ($itemIds->isEmpty()) {
            $service->finalize($batch);
            return;
        }

        // Sync queue: process inline (Bus::batch not supported on sync)
        if (Config::get('queue.default') === 'sync') {
            $itemIds->each(fn ($id) => Bus::dispatchSync(new PayEmployeeJob($id)));
            $service->finalize($batch);
            return;
        }

        $jobs = $itemIds->map(fn ($id) => new PayEmployeeJob($id))->all();
        $batchId = $batch->id;

        try {
            Bus::batch($jobs)
                ->name("payroll:{$batchId}")
                ->allowFailures()
                ->finally(function (Batch $b) use ($batchId) {
                    if ($fresh = PayrollBatch::find($batchId)) {
                        app(PayrollService::class)->finalize($fresh);
                    }
                })
                ->dispatch();
        } catch (\Throwable $e) {
            logger()->warning('Payroll batch dispatch failed, falling back to sync: ' . $e->getMessage());
            $itemIds->each(fn ($id) => Bus::dispatchSync(new PayEmployeeJob($id)));
            $service->finalize($batch);
        }
    }

    public function failed(Throwable $e): void
    {
        if ($batch = PayrollBatch::find($this->batchId)) {
            app(PayrollService::class)->finalize($batch);
        }
    }
}
