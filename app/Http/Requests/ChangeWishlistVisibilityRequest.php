<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\WishlistService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangeWishlistVisibilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(WishlistService $wishlistService): bool
    {
        $slug = $this->route('slug');
        /** @var User|null $user */
        $user = $this->user();

        $wishlist = $wishlistService->getWishlistByUserIdAndSlug($user->id, $slug);
        if (is_null($wishlist)) {
            return false;
        }

        return $user->can('update-wishlist-visibility', $wishlist);
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
