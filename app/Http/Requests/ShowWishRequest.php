<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\Wish;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowWishRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Wish $wish */
        $wish = $this->route('wish');
        /** @var User|null $user */
        $user = $this->user();

        if (!$wish->wishlist->is_private) {
            return true;
        }

        if ($user === null && $wish->wishlist->is_private) {
            return false;
        }

        return $user->can('show-wish', $wish);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}
