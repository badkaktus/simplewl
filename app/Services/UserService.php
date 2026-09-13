<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Str;

class UserService
{
    private const FALLBACK_NAME = 'user';

    public function __construct(private readonly UserRepository $userRepository) {}

    public function getUserByName(string $name): ?User
    {
        return $this->userRepository->getUserByName($name);
    }

    /**
     * The name is used in public wishlist URLs, so it has to be unique.
     */
    public function generateUniqueName(?string $preferredName): string
    {
        $name = Str::limit(trim(str_replace('/', '-', (string) $preferredName)), 240, '');
        if ($name === '' || in_array($name, User::RESERVED_NAMES, true)) {
            $name = self::FALLBACK_NAME;
        }

        $uniqueName = $name;
        $i = 2;
        while ($this->userRepository->existsByName($uniqueName)) {
            $uniqueName = $name.'-'.$i;
            $i++;
        }

        return $uniqueName;
    }
}
