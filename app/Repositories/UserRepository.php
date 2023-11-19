<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function getUserByName(string $name): ?User
    {
        if (!$user = User::where('name', $name)->first()) {
            return null;
        }
        return $user;
    }
}
