<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Providers\RouteServiceProvider;
use App\Services\Auth\Telegram;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class TelegramController extends Controller
{
    public function handleTelegramCallback(): RedirectResponse|JsonResponse
    {
        try {
            $telegramUser = Socialite::driver('telegram')->user();
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        $user = (new Telegram())->findUser($telegramUser);

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
