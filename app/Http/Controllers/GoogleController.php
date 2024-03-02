<?php

namespace App\Http\Controllers;

use App\Providers\RouteServiceProvider;
use App\Services\Auth\Google;
use Auth;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class GoogleController extends Controller
{
    public function redirectToGoogle(): RedirectResponse|\Illuminate\Http\RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse|JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        $user = (new Google())->findUser($googleUser);

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
