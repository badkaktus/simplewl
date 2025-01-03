<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Wish;
use Illuminate\Support\Collection;

class WishRepository
{
    public function create(
        string $title,
        int $wishlistId,
        string $slug,
        ?string $desc = null,
        ?string $url = null,
        ?string $imageUrl = null,
        ?string $localFileName = null,
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
            'local_file_name' => $localFileName,
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }

    public function getWish(int $id): Wish
    {
        return Wish::findOrFail($id);
    }

    public function getWishBySlugAndUserId(string $slug, int $userId): Wish
    {
        return Wish::where('slug', $slug)
            ->whereHas('wishlist', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->firstOrFail();
    }

    public function getUserWishesByWishlistId(int $userId, int $wishlistId): Collection
    {
        return Wish::where('wishlist_id', $wishlistId)
            ->whereHas('wishlist', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('is_completed')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getWishBySlugAndWishlistId(string $slug, int $wishlistId): ?Wish
    {
        return Wish::where('slug', $slug)
            ->where('wishlist_id', $wishlistId)
            ->first();
    }
}
