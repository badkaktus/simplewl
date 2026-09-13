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

class Telegram implements AuthProviderInterface
{
    public function __construct(private readonly UserService $userService) {}

    public function findUser(SocialiteUser $user): User
    {
        $findUser = UserAttributes::whereTelegramId($user->getId())->first();
        if ($findUser) {
            return $findUser->user;
        }

        // Telegram does not provide a verified email, and the name is chosen freely,
        // so an unknown Telegram account always gets a new user.
        $newUser = User::create([
            'name' => $this->userService->generateUniqueName($user->getNickname()),
            'password' => Hash::make(Str::password()),
        ]);

        $attributes = new UserAttributes([
            'telegram_id' => $user->getId(),
        ]);
        $newUser->attributes()->save($attributes);

        event(new Registered($newUser));

        return $newUser;
    }
}
