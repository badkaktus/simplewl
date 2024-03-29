<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DestroyWishRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wish = $this->route('wish');
        /** @var User|null $user */
        $user = $this->user();

        return $user->can('delete-wish', $wish);
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
