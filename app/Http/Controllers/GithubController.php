<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Providers\RouteServiceProvider;
use App\Services\Auth\Github;
use Auth;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class GithubController extends Controller
{
    public function redirectToGithub(): RedirectResponse|\Illuminate\Http\RedirectResponse
    {
        return Socialite::driver('github')->redirect();
    }

    public function handleGithubCallback(): RedirectResponse|JsonResponse
    {
        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        $user = (new Github)->findUser($githubUser);

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
