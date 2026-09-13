<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserAttributes;
use App\Services\UserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Str;

class Github implements AuthProviderInterface
{
    public function __construct(private readonly UserService $userService) {}

    public function findUser(SocialiteUser $user): User
    {
        $findUser = UserAttributes::whereGithubId($user->getId())->first();
        if ($findUser) {
            return $findUser->user;
        }

        // Without this check whereEmail(null) matches the first user without email
        $email = $user->getEmail();
        $existingUser = $email ? User::whereEmail($email)->first() : null;

        if (! $existingUser) {
            $existingUser = User::create([
                'name' => $this->userService->generateUniqueName($user->getNickname()),
                'email' => $email,
                'password' => Hash::make(Str::password()),
            ]);
        }

        $attributes = new UserAttributes([
            'github_id' => $user->getId(),
        ]);
        $existingUser->attributes()->save($attributes);

        event(new Registered($existingUser));

        return $existingUser;
    }
}
