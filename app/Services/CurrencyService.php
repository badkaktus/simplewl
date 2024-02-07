<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    public function syncCurrencies(): void
    {
        $apiKey = config('services.currencybeacon.api_key');
        if (! $apiKey) {
            logger()->error('Currency beacon API key not found');

            return;
        }

        $response = Http::get("https://api.currencybeacon.com/v1/currencies?api_key={$apiKey}");

        $collections = Currency::hydrate($response->collect()->get('response'));

        if ($collections->isEmpty()) {
            logger()->error('No currencies found');

            return;
        }

        foreach ($collections as $collection) {
            Currency::updateOrCreate(
                ['id' => $collection->id],
                [
                    'name' => $collection->name,
                    'short_code' => $collection->short_code,
                    'precision' => $collection->precision,
                    'subunit' => $collection->subunit,
                    'code' => $collection->code,
                    'symbol' => $collection->symbol,
                    'symbol_first' => $collection->symbol_first,
                ]
            );
        }
    }
}
