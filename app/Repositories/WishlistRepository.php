<?php

namespace App\Repositories;

use App\Models\Wishlist;

class WishlistRepository
{
    public function findFirstOrCreate(int $userId, string $title, string $slug): Wishlist
    {
        return Wishlist::firstOrCreate(['user_id' => $userId, 'title' => $title, 'slug' => $slug]);
    }

    public function getWishlistByUserIdAndSlug(string $slug): Wishlist
    {
        return Wishlist::where('slug', $slug)->first();
    }
}
