<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAttributes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAttributes>
 */
class UserAttributesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'id' => $user->id,
        ];
    }
}
