<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Requests\StoreWishRequest;
use App\Http\Requests\UpdateWishRequest;
use App\Models\User;
use App\Models\Wish;
use App\Models\Wishlist;
use App\Repositories\WishlistRepository;
use App\Repositories\WishRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WishService
{
    public function __construct(
        private readonly WishRepository $wishRepository,
        private readonly WishlistRepository $wishlistRepository
    ) {
    }

    public function createWish(StoreWishRequest $request): Wish
    {
        $userId = Auth::id();

        if (!$userId) {
            throw new \Exception('User not found');
        }

        $wishlist = $this->wishlistRepository->findFirstOrCreate(
            $userId,
            Wishlist::DEFAULT_WISHLIST_TITLE,
            Wishlist::DEFAULT_WISHLIST_SLUG
        );

        return $this->wishRepository->create(
            $request->title,
            $wishlist->id,
            Str::slug($request->title),
            $request->description ?? null,
            $request->url ?? null,
            $request->image_url ?? null,
            $request->amount ? $request->float('amount') : null,
            $request->currency ?? null
        );
    }

    public function updateWish(UpdateWishRequest $request, int $id): Wish
    {
        $wish = $this->wishRepository->getWish($id);

        $wish->update([
            'title' => $request->title,
            'description' => $request->description ?? null,
            'url' => $request->url ?? null,
            'image_url' => $request->image_url ?? null,
            'amount' => $request->amount ? $request->float('amount') : null,
            'currency' => $request->currency ?? null,
            'slug' => Str::slug($request->title),
        ]);

        return $wish;
    }

    public function changeCompletedStatus(string $wishSlug): Wish
    {
        $wish = $this->wishRepository->getWishBySlug($wishSlug);
        $wish->update([
            'is_completed' => (int)!$wish->is_completed
        ]);
        $wish->refresh();
        return $wish;
    }

    public function getWish(int $id): Wish
    {
        return Wish::findOrFail($id);
    }

    public function getWishes(?string $wishlistSlug): Collection
    {
        $userId = Auth::id();

        if (!$wishlistSlug) {
            $wishlist = $this->wishlistRepository->findFirstOrCreate(
                $userId,
                Wishlist::DEFAULT_WISHLIST_TITLE,
                Wishlist::DEFAULT_WISHLIST_SLUG
            );
            $wishlistId = $wishlist->id;
        } else {
            $wishlist = $this->wishlistRepository->getWishlistByUserIdAndSlug($wishlistSlug);
            $wishlistId = $wishlist->id;
        }

        return $this->wishRepository->getUserWishesByWishlistId($userId, $wishlistId);
    }

    public function deleteWish(Wish $wish): void
    {
        $wish->delete();
    }
}
