<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TryToOpenPrivateWishlist;
use App\Http\Requests\StoreWishRequest;
use App\Http\Requests\UpdateWishRequest;
use App\Models\Wish;
use App\Models\Wishlist;
use App\Repositories\WishlistRepository;
use App\Repositories\WishRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WishService
{
    public function __construct(
        private readonly WishRepository $wishRepository,
        private readonly WishlistRepository $wishlistRepository,
        private readonly UserService $userService
    ) {
    }

    public function createWish(StoreWishRequest $request): Wish
    {
        $userId = Auth::id();

        if (! $userId) {
            throw new \Exception('User not found');
        }

        $wishlist = $this->wishlistRepository->findFirstOrCreate(
            $userId,
            Wishlist::DEFAULT_WISHLIST_TITLE,
            Wishlist::DEFAULT_WISHLIST_SLUG
        );

        $localImageName = $request->image_url ? $this->saveImageToLocal($request->image_url) : null;

        return $this->wishRepository->create(
            $request->title,
            $wishlist->id,
            Str::slug($request->title),
            $request->description ?? null,
            $request->url ?? null,
            $request->image_url ?? null,
            $localImageName,
            $request->amount ? $request->float('amount') : null,
            $request->currency ?? null
        );
    }

    public function updateWish(UpdateWishRequest $request, string $slug): Wish
    {
        $wish = $this->wishRepository->getWishBySlugAndUserId($slug, Auth::id());
        // todo add check for image_url and if it is changed, save new image to local
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
        $wish = $this->wishRepository->getWishBySlugAndUserId($wishSlug, Auth::id());
        $wish->update([
            'is_completed' => (int) ! $wish->is_completed,
        ]);
        $wish->refresh();

        return $wish;
    }

    public function getWishesByUserAndSlug(?string $username, ?string $wishlistSlug): Collection
    {
        $user = (is_null($username)) ? Auth::user() : $this->userService->getUserByName($username);

        if (! $user) {
            return collect();
        }

        if (! $wishlistSlug) {
            $wishlistSlug = Wishlist::DEFAULT_WISHLIST_SLUG;
        }

        $wishlist = $this->wishlistRepository->getWishlistByUserIdAndSlug($user->id, $wishlistSlug);
        if (! $wishlist) {
            return collect();
        }

        if ($wishlist->is_private && $user->id !== Auth::id()) {
            throw new TryToOpenPrivateWishlist('Wishlist is private');
        }

        return $this->wishRepository->getUserWishesByWishlistId($user->id, $wishlist->id);
    }

    public function deleteWish(Wish $wish): void
    {
        $wish->delete();
    }

    private function saveImageToLocal(string $imageUrl): ?string
    {
        $response = Http::get($imageUrl);
        if ($response->failed()) {
            return null;
        }

        $position = strpos($imageUrl, '?');
        if ($position !== false) {
            $imageUrl = substr($imageUrl, 0, $position);
        }

        $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);

        $filename = 'wishes/'.uniqid('image_', true).'.'.$extension;

        Storage::disk('public')->put($filename, $response->body());

        return $filename;
    }
}
