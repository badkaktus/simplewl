<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\UserAttributes;
use App\Models\Wishlist;
use App\Providers\RouteServiceProvider;
use Symfony\Component\HttpFoundation\Response;

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

    public function test_github_login_without_email_does_not_take_over_user_without_email(): void
    {
        $githubIdUser = random_int(100, 1000);
        $victim = User::factory()->create([
            'email' => null,
        ]);

        $this->mockUser('github', $githubIdUser, fake()->userName);

        $response = $this->get('/auth/github/callback');
        $response->assertRedirect(RouteServiceProvider::HOME);

        $newUser = UserAttributes::whereGithubId($githubIdUser)->first()->user;

        $this->assertNotSame($victim->id, $newUser->id);
        $this->assertDatabaseHas('users', [
            'id' => $newUser->id,
            'email' => null,
        ]);
        $this->assertAuthenticatedAs($newUser);
        $this->assertDatabaseMissing('user_attributes', [
            'id' => $victim->id,
        ]);
    }

    public function test_github_login_generates_unique_name(): void
    {
        $githubIdUser = random_int(100, 1000);
        $existingUser = User::factory()->create();

        $this->mockUser('github', $githubIdUser, $existingUser->name, \Str::random(10).'@test.com');

        $response = $this->get('/auth/github/callback');
        $response->assertRedirect(RouteServiceProvider::HOME);

        $newUser = UserAttributes::whereGithubId($githubIdUser)->first()->user;

        $this->assertNotSame($existingUser->id, $newUser->id);
        $this->assertSame($existingUser->name.'-2', $newUser->name);
    }

    public function test_failed_validation(): void
    {
        $response = $this->get('/auth/github/callback');
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertExactJson([
            'error' => 'Invalid request',
        ]);
    }
}
