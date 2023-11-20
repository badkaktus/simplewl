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
            'url' => ['nullable', 'url'],
            'image_url' => ['nullable', 'url'],
            'amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string'],
        ];
    }
}
