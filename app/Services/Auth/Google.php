<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserAttributes;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Str;

class Google implements AuthProviderInterface
{
    public function findUser(SocialiteUser $user): User
    {
        $findUser = UserAttributes::whereGoogleId($user->getId())->first();
        if ($findUser) {
            return $findUser->user;
        }

        $existingUser = User::whereEmail($user->getEmail())->first();

        if (! $existingUser) {
            $existingUser = User::create([
                'name' => $user->getNickname(),
                'email' => $user->getEmail(),
                'password' => Hash::make(Str::password()),
            ]);
        }

        $attributes = new UserAttributes([
            'google_id' => $user->getId(),
        ]);
        $existingUser->attributes()->save($attributes);

        event(new Registered($existingUser));

        return $existingUser;
    }
}
