<?php

declare(strict_types=1);

namespace App\Http\Requests;

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
            'currency' => ['nullable', 'string'],
        ];
    }
}
