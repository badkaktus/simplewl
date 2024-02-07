<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CurrencyService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyServiceTest extends TestCase
{
    public function test_currency_sync(): void
    {
        Http::fake([
            'api.currencybeacon.com/*' => Http::response(
                '{"0":{"id":1,"name":"UAE Dirham","short_code":"AED","code":"784","precision":2,"subunit":100,"symbol":"د.إ","symbol_first":true,"decimal_mark":".","thousands_separator":","},"1":{"id":25,"name":"Belarussian Ruble","short_code":"BYN","code":"974","precision":0,"subunit":1,"symbol":"Br","symbol_first":false,"decimal_mark":",","thousands_separator":" "},"meta":{"code":200,"disclaimer":"Usage subject to terms: https://currencybeacon.com/terms"},"response":[{"id":1,"name":"UAE Dirham","short_code":"AED","code":"784","precision":2,"subunit":100,"symbol":"د.إ","symbol_first":true,"decimal_mark":".","thousands_separator":","},{"id":25,"name":"Belarussian Ruble","short_code":"BYN","code":"974","precision":0,"subunit":1,"symbol":"Br","symbol_first":false,"decimal_mark":",","thousands_separator":" "}]}',
            ),
        ]);

        app(CurrencyService::class)->syncCurrencies();

        $this->assertDatabaseHas('currencies', [
            'name' => 'UAE Dirham',
            'short_code' => 'AED',
            'code' => '784',
            'precision' => 2,
            'subunit' => 100,
            'symbol' => 'د.إ',
            'symbol_first' => true,
        ]);
        $this->assertDatabaseHas('currencies', [
            'name' => 'Belarussian Ruble',
            'short_code' => 'BYN',
            'code' => '974',
            'precision' => 0,
            'subunit' => 1,
            'symbol' => 'Br',
            'symbol_first' => false,
        ]);
    }
}
