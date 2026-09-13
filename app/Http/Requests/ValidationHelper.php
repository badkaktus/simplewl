<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

class ValidationHelper
{
    public static function getWishValidationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'url' => ['nullable', 'url:http,https'],
            'image_url' => ['nullable', 'url:http,https'],
            'amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'alpha:ascii', 'min:3', 'max:4'],
        ];
    }

    /**
     * The name is a segment of public wishlist URLs, so it has to be unique and URL-safe.
     */
    public static function getUserNameRules(?int $ignoreUserId = null): array
    {
        return [
            'string',
            'max:255',
            'not_regex:#/#',
            Rule::notIn(User::RESERVED_NAMES),
            Rule::unique(User::class, 'name')->ignore($ignoreUserId),
        ];
    }
}
