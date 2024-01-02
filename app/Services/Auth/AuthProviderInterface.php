<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

interface AuthProviderInterface
{
    public function findUser(SocialiteUser $user): User;
}
