<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Currency;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testSuccess(): void
    {
        $currency1 = Currency::factory()->create();
        $currency2 = Currency::factory()->create();
        $currency3 = Currency::factory()->create();
        $response = $this->get('/currency/all');
        $response->assertStatus(200);
        $response->assertJsonIsArray();
        $response->assertJsonCount(3);
        $response->assertJsonFragment([
            'id' => $currency1->id,
            'name' => $currency1->name,
            'short_code' => $currency1->short_code,
        ]);
        $response->assertJsonFragment([
            'id' => $currency2->id,
            'name' => $currency2->name,
            'short_code' => $currency2->short_code,
        ]);
        $response->assertJsonFragment([
            'id' => $currency3->id,
            'name' => $currency3->name,
            'short_code' => $currency3->short_code,
        ]);
        $this->assertDatabaseCount('currencies', 3);
        $this->assertDatabaseHas('currencies', [
            'id' => $currency1->id,
            'name' => $currency1->name,
            'short_code' => $currency1->short_code,
        ]);
        $this->assertDatabaseHas('currencies', [
            'id' => $currency2->id,
            'name' => $currency2->name,
            'short_code' => $currency2->short_code,
        ]);
        $this->assertDatabaseHas('currencies', [
            'id' => $currency3->id,
            'name' => $currency3->name,
            'short_code' => $currency3->short_code,
        ]);
    }
}
