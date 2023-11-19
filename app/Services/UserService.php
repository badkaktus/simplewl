<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

class UserService
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function getUserByName(string $name): ?User
    {
        return $this->userRepository->getUserByName($name);
    }
}
