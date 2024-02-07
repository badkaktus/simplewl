<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'short_code' => $this->faker->currencyCode(),
            'code' => (string)$this->faker->numberBetween(1, 100),
            'precision' => $this->faker->numberBetween(0, 4),
            'subunit' => $this->faker->numberBetween(50, 150),
            'symbol' => $this->faker->randomLetter(),
            'symbol_first' => $this->faker->boolean(),
        ];
    }
}
