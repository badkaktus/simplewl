<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Wish;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WishRepository
{
    public function create(
        string $title,
        int $wishlistId,
        string $slug,
        ?string $desc = null,
        ?string $url = null,
        ?string $imageUrl = null,
        ?float $amount = null,
        ?string $currency = null
    ): Wish {
        return Wish::create([
            'title' => $title,
            'slug' => $slug,
            'wishlist_id' => $wishlistId,
            'description' => $desc,
            'url' => $url,
            'image_url' => $imageUrl,
            'amount' => $amount,
            'currency' => $currency
        ]);
    }

    public function getWish(int $id): Wish
    {
        return Wish::findOrFail($id);
    }

    public function getWishBySlug(string $slug): Wish
    {
        return Wish::where('slug', $slug)->firstOrFail();
    }

    public function getUserWishesByWishlistId(int $userId, int $wishlistId): Collection
    {
        $wishes = DB::table('wishes')
            ->select('wishes.*')
            ->leftJoin('wishlists', 'wishlists.id', '=', 'wishes.wishlist_id')
            ->where('wishlists.user_id', '=', $userId)
            ->where('wishlists.id', '=', $wishlistId)
            ->orderBy('wishes.created_at', 'desc');
        return $wishes->get();
    }
}
