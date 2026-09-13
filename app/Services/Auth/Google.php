<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Two\User as OAuthTwoUser;

class Google extends AbstractAuthProvider
{
    protected function providerIdColumn(): string
    {
        return 'google_id';
    }

    protected function getVerifiedEmail(SocialiteUser $user): ?string
    {
        // The Google provider does not check that the email is verified
        if (! $user instanceof OAuthTwoUser || ($user->getRaw()['email_verified'] ?? false) !== true) {
            return null;
        }

        return $user->getEmail();
    }
}
