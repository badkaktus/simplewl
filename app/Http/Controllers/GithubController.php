<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Providers\RouteServiceProvider;
use App\Services\Auth\Github;
use Auth;
use Exception;
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
        try {
            $githubUser = Socialite::driver('github')->user();

            $user = (new Github())->findUser($githubUser);

            Auth::login($user);

            return redirect(RouteServiceProvider::HOME);
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}
