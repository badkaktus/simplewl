<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Providers\RouteServiceProvider;
use App\Services\Auth\Github;
use Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GithubController extends Controller
{
    public function redirectToGithub(): RedirectResponse|\Illuminate\Http\RedirectResponse
    {
        return Socialite::driver('github')->redirect();
    }

    public function handleGithubCallback(): RedirectResponse
    {
        $githubUser = Socialite::driver('github')->user();

        $user = (new Github())->findUser($githubUser);

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
