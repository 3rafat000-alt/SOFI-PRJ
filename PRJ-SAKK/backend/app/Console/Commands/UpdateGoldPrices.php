<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\GoldPriceService;
use Illuminate\Console\Command;

class UpdateGoldPrices extends Command
{
    protected $signature = 'gold:update-prices {--force : Run even when auto-update is disabled}';

    protected $description = 'Fetch the global gold spot price and refresh per-karat buy/sell prices';

    public function handle(GoldPriceService $service): int
    {
        if (!$this->option('force') && !$service->isAutoEnabled()) {
            $this->info('Gold auto-update disabled — skipping. Enable it in the admin panel or pass --force.');
            return self::SUCCESS;
        }

        $result = $service->refresh();

        if (!$result['success']) {
            $this->error($result['message'] ?? 'Gold price refresh failed.');
            return self::FAILURE;
        }

        $this->info(sprintf(
            'Updated %d karats from spot $%.4f/g (24k), margin %.2f%%.',
            $result['updated'],
            $result['spot'],
            $result['margin'],
        ));

        return self::SUCCESS;
    }
}
