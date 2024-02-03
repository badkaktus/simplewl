<?php

namespace App\Http\Controllers;

use App\Providers\RouteServiceProvider;
use App\Services\Auth\Google;
use Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleController extends Controller
{
    public function redirectToGoogle(): RedirectResponse|\Illuminate\Http\RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = (new Google())->findUser($googleUser);

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
