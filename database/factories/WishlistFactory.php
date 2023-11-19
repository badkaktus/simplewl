<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Wishlist>
 */
class WishlistFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(5, true);
        $user = User::factory()->create();
        $userId = $user->id;
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'user_id' => $userId,
            'is_private' => 0,
        ];
    }
}
