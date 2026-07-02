<?php

namespace App\Jobs;

use App\Models\PayrollItem;
use App\Services\PayrollService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Pay (or hold) a single payroll item. ShouldBeUnique keyed on the item id so a
 * re-queued/duplicated job can never pay twice; PayrollService::processItem also
 * re-checks the item status under a row lock as a second guard.
 */
class PayEmployeeJob implements ShouldQueue, ShouldBeUnique
{
    use Batchable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public int $payrollItemId) {}

    public function uniqueId(): string
    {
        return 'payroll-item-' . $this->payrollItemId;
    }

    public function handle(PayrollService $service): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $item = PayrollItem::find($this->payrollItemId);
        if (!$item) {
            return;
        }

        $service->processItem($item);
    }
}
