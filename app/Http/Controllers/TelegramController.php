<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Providers\RouteServiceProvider;
use App\Services\Auth\Telegram;
use Auth;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class TelegramController extends Controller
{
    public function handleTelegramCallback(): RedirectResponse
    {
        $telegramUser = Socialite::driver('telegram')->user();
        $user = (new Telegram())->findUser($telegramUser);

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
