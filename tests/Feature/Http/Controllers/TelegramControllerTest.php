<?php

declare(strict_types=1);

namespace Feature\Http\Controllers;

use App\Models\User;
use App\Models\Wishlist;
use App\Providers\RouteServiceProvider;
use Tests\Feature\Http\Controllers\AbstractThirdPartyAuthController;

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
}
