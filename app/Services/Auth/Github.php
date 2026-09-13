<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Laravel\Socialite\Contracts\User as SocialiteUser;

class Github extends AbstractAuthProvider
{
    protected function providerIdColumn(): string
    {
        return 'github_id';
    }

    protected function getVerifiedEmail(SocialiteUser $user): ?string
    {
        // The GitHub provider returns only the primary verified email
        return $user->getEmail();
    }
}
