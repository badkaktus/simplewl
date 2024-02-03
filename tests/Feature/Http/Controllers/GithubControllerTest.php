<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\UserAttributes;
use App\Models\Wishlist;
use App\Providers\RouteServiceProvider;

class GithubControllerTest extends AbstractThirdPartyAuthController
{
    public function test_redirect_to_github(): void
    {
        $response = $this->get('/auth/github');
        $response->assertRedirect();
    }

    public function test_successfully_login_to_github(): void
    {
        $githubIdUser = random_int(100, 1000);
        $githubEmailUser = \Str::random(10).'@test.com';
        $githubNameUser = fake()->name;

        $this->mockUser('github', $githubIdUser, $githubNameUser, $githubEmailUser);

        $response = $this->get('/auth/github/callback');
        $response->assertStatus(302);
        $response->assertRedirect(RouteServiceProvider::HOME);

        $this->assertDatabaseHas('users', [
            'email' => $githubEmailUser,
            'name' => $githubNameUser,
        ]);
        $this->assertDatabaseHas('user_attributes', [
            'github_id' => $githubIdUser,
        ]);
        $this->assertDatabaseHas('wishlists', [
            'user_id' => User::whereEmail($githubEmailUser)->first()->id,
            'slug' => Wishlist::DEFAULT_WISHLIST_SLUG,
        ]);
    }

    public function test_successfully_login_to_github_existing_user(): void
    {
        $githubIdUser = random_int(100, 1000);
        $githubEmailUser = \Str::random(10).'@test.com';
        $githubNameUser = fake()->name;

        $user = User::factory()->create([
            'email' => $githubEmailUser,
            'name' => $githubNameUser,
        ]);

        UserAttributes::factory()->create([
            'id' => $user->id,
            'github_id' => $githubIdUser,
        ]);

        $this->mockUser('github', $githubIdUser, $githubNameUser, $githubEmailUser);

        $response = $this->get('/auth/github/callback');
        $response->assertStatus(302);
        $response->assertRedirect(RouteServiceProvider::HOME);
    }
}
