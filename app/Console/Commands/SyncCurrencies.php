<?php

namespace App\Console\Commands;

use App\Services\CurrencyService;
use Illuminate\Console\Command;

class SyncCurrencies extends Command
{
    /**
     * @var string
     */
    protected $signature = 'currency:sync';

    /**
     * @var string
     */
    protected $description = 'Sync currencies from currencybeacon.com';

    public function handle(CurrencyService $currencyService): void
    {
        $this->info('Syncing currencies...');

        $currencyService->syncCurrencies();

        $this->info('Currencies synced');
    }
}
