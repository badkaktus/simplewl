<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserAttributes;
use App\Services\UserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

abstract class AbstractAuthProvider implements AuthProviderInterface
{
    public function __construct(private readonly UserService $userService) {}

    public function findUser(SocialiteUser $user): User
    {
        $attributes = UserAttributes::where($this->providerIdColumn(), $user->getId())->first();
        if ($attributes?->user) {
            return $attributes->user;
        }

        $existingUser = $this->findExistingUser($user);
        $appUser = $existingUser ?? User::create([
            'name' => $this->userService->generateUniqueName($user->getNickname()),
            'email' => $this->getVerifiedEmail($user),
            'password' => Hash::make(Str::password()),
        ]);

        // A user has a single attributes row, a new provider is added to it
        $appUser->attributes()->updateOrCreate([], [
            $this->providerIdColumn() => $user->getId(),
        ]);

        if (! $existingUser instanceof User) {
            event(new Registered($appUser));
        }

        return $appUser;
    }

    /**
     * Column of the user_attributes table with the user id in the provider.
     */
    abstract protected function providerIdColumn(): string;

    /**
     * Email that the provider has verified, so it's safe to link the login to an existing user.
     */
    abstract protected function getVerifiedEmail(SocialiteUser $user): ?string;

    private function findExistingUser(SocialiteUser $user): ?User
    {
        // Without this check whereEmail(null) matches the first user without email
        $email = $this->getVerifiedEmail($user);

        return $email ? User::whereEmail($email)->first() : null;
    }
}
