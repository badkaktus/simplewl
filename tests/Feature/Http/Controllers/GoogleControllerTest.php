<?php

declare(strict_types=1);

namespace Feature\Http\Controllers;

use App\Models\User;
use App\Models\UserAttributes;
use App\Models\Wishlist;
use App\Providers\RouteServiceProvider;
use Tests\Feature\Http\Controllers\AbstractThirdPartyAuthController;

class GoogleControllerTest extends AbstractThirdPartyAuthController
{
    public function test_redirect_to_google(): void
    {
        $response = $this->get('/auth/google');
        $response->assertRedirect();
    }

    public function test_successfully_login_to_google(): void
    {
        $googleIdUser = random_int(100, 1000);
        $googleEmailUser = \Str::random(10).'@test.com';
        $googleNameUser = fake()->name;

        $this->mockUser('google', $googleIdUser, $googleNameUser, $googleEmailUser);

        $response = $this->get('/auth/google/callback');
        $response->assertStatus(302);
        $response->assertRedirect(RouteServiceProvider::HOME);

        $this->assertDatabaseHas('users', [
            'email' => $googleEmailUser,
            'name' => $googleNameUser,
        ]);
        $this->assertDatabaseHas('user_attributes', [
            'google_id' => $googleIdUser,
        ]);
        $this->assertDatabaseHas('wishlists', [
            'user_id' => User::whereEmail($googleEmailUser)->first()->id,
            'slug' => Wishlist::DEFAULT_WISHLIST_SLUG,
        ]);
    }

    public function test_successfully_login_to_google_existing_user(): void
    {
        $googleIdUser = random_int(100, 1000);
        $googleEmailUser = \Str::random(10).'@test.com';
        $googleNameUser = fake()->name;

        $user = User::factory()->create([
            'email' => $googleEmailUser,
            'name' => $googleNameUser,
        ]);

        UserAttributes::factory()->create([
            'id' => $user->id,
            'google_id' => $googleIdUser,
        ]);

        $this->mockUser('google', $googleIdUser, $googleNameUser, $googleEmailUser);

        $response = $this->get('/auth/google/callback');
        $response->assertStatus(302);
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_successfully_login_when_user_exist_but_google_id_is_not(): void
    {
        $googleIdUser = random_int(100, 1000);
        $googleEmailUser = \Str::random(10).'@test.com';
        $googleNameUser = fake()->name;

        $user = User::factory()->create([
            'email' => $googleEmailUser,
            'name' => fake()->name,
        ]);

        $this->mockUser('google', $googleIdUser, $googleNameUser, $googleEmailUser);

        $response = $this->get('/auth/google/callback');
        $response->assertStatus(302);
        $response->assertRedirect(RouteServiceProvider::HOME);

        $this->assertDatabaseHas('user_attributes', [
            'google_id' => $googleIdUser,
        ]);
    }
}
