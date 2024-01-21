<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wishlist;
use App\Repositories\WishlistRepository;

class WishlistService
{
    public function __construct(private readonly WishlistRepository $wishlistRepository)
    {
    }

    public function createWishlist(User $user, ?string $title = null, ?string $slug = null): Wishlist
    {
        $title = $title ?? Wishlist::DEFAULT_WISHLIST_TITLE;
        $slug = $slug ?? Wishlist::DEFAULT_WISHLIST_SLUG;

        return $this->wishlistRepository->findFirstOrCreate($user->id, $title, $slug);
    }

    public function getWishlistByUserIdAndSlug(int $userId, string $slug): ?Wishlist
    {
        return $this->wishlistRepository->getWishlistByUserIdAndSlug($userId, $slug);
    }

    public function changeVisibility(Wishlist $wishlist): Wishlist
    {
        $wishlist->update([
            'is_private' => ! $wishlist->is_private,
        ]);

        return $wishlist;
    }
}
