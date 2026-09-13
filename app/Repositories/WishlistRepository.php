<?php

namespace App\Repositories;

use App\Models\Wishlist;

class WishlistRepository
{
    public function findFirstOrCreate(int $userId, string $title, string $slug): Wishlist
    {
        // The slug is unique for the user, the title is only used for a new wishlist
        return Wishlist::firstOrCreate(['user_id' => $userId, 'slug' => $slug], ['title' => $title]);
    }

    public function getWishlistByUserIdAndSlug(int $userId, string $slug): ?Wishlist
    {
        return Wishlist::where('slug', $slug)->where('user_id', $userId)->first();
    }
}
