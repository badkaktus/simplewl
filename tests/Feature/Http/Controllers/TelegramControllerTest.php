<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\UserAttributes;
use App\Models\Wishlist;
use App\Providers\RouteServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class TelegramControllerTest extends AbstractThirdPartyAuthController
{
    public function test_successfully_login_to_telegram(): void
    {
        $tgIdUser = random_int(100, 1000);
        $tgUsername = fake()->userName;

        $this->mockUser('telegram', $tgIdUser, $tgUsername);

        $response = $this->get('/auth/telegram/callback');
        $response->assertStatus(302);
        $response->assertRedirect(RouteServiceProvider::HOME);

        $this->assertDatabaseHas('users', [
            'name' => $tgUsername,
        ]);
        $this->assertDatabaseHas('user_attributes', [
            'telegram_id' => $tgIdUser,
        ]);
        $this->assertDatabaseHas('wishlists', [
            'user_id' => User::whereName($tgUsername)->first()->id,
            'slug' => Wishlist::DEFAULT_WISHLIST_SLUG,
        ]);
    }

    public function test_telegram_login_does_not_take_over_user_with_same_name(): void
    {
        $tgIdUser = random_int(100, 1000);
        $victim = User::factory()->create();

        $this->mockUser('telegram', $tgIdUser, $victim->name);

        $response = $this->get('/auth/telegram/callback');
        $response->assertRedirect(RouteServiceProvider::HOME);

        $newUser = UserAttributes::whereTelegramId($tgIdUser)->first()->user;

        $this->assertNotSame($victim->id, $newUser->id);
        $this->assertAuthenticatedAs($newUser);
        $this->assertSame($victim->name.'-2', $newUser->name);
        $this->assertDatabaseMissing('user_attributes', [
            'id' => $victim->id,
        ]);
    }

    public function test_existing_telegram_user_logs_into_his_account(): void
    {
        $tgIdUser = random_int(100, 1000);
        $user = User::factory()->create();
        UserAttributes::factory()->create([
            'id' => $user->id,
            'telegram_id' => $tgIdUser,
        ]);
        $usersCount = User::count();

        $this->mockUser('telegram', $tgIdUser, fake()->userName);

        $response = $this->get('/auth/telegram/callback');
        $response->assertRedirect(RouteServiceProvider::HOME);

        $this->assertAuthenticatedAs($user);
        $this->assertSame($usersCount, User::count());
    }

    public function test_failed_validation(): void
    {
        $response = $this->get('/auth/telegram/callback');
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertExactJson([
            'error' => 'Invalid request',
        ]);
    }
}
