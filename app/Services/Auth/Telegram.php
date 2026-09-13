<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Laravel\Socialite\Contracts\User as SocialiteUser;

class Telegram extends AbstractAuthProvider
{
    protected function providerIdColumn(): string
    {
        return 'telegram_id';
    }

    protected function getVerifiedEmail(SocialiteUser $user): ?string
    {
        // Telegram does not provide an email, so an unknown Telegram account always gets a new user
        return null;
    }
}
