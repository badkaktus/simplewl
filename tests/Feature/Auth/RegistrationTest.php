<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Wishlist;
use App\Providers\RouteServiceProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_new_users_have_default_wishlist(): void
    {
        $email = fake()->email;
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(302);

        $user = User::where('email', $email)->first();

        $this->assertDatabaseHas(Wishlist::TABLE_NAME, [
            'user_id' => $user->id,
            'title' => Wishlist::DEFAULT_WISHLIST_TITLE,
            'slug' => Wishlist::DEFAULT_WISHLIST_SLUG,
        ]);
    }
}
