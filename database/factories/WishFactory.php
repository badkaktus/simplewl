<?php

namespace Database\Factories;

use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wish>
 */
class WishFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(5, true);

        return [
            'title' => $title,
            'description' => fake()->paragraph(),
            'url' => fake()->url(),
            'amount' => fake()->numberBetween(100, 10000),
            'currency' => fake()->currencyCode(),
            'image_url' => fake()->imageUrl(),
            'is_completed' => 0,
            'wishlist_id' => Wishlist::factory()->create()->id,
            'slug' => Str::slug($title),
        ];
    }
}
