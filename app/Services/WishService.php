<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TryToOpenPrivateWishlist;
use App\Http\Requests\StoreWishRequest;
use App\Http\Requests\UpdateWishRequest;
use App\Models\User;
use App\Models\Wish;
use App\Models\Wishlist;
use App\Repositories\WishlistRepository;
use App\Repositories\WishRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WishService
{
    private const IMAGES_DIRECTORY = 'wishes';

    public function __construct(
        private readonly WishRepository $wishRepository,
        private readonly WishlistRepository $wishlistRepository,
        private readonly UserService $userService,
        private readonly ImageDownloader $imageDownloader,
    ) {}

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
            $this->getSlugForWish($request->title, $wishlist->id),
            $request->description ?? null,
            $request->url ?? null,
            $request->image_url ?? null,
            $localImageName,
            $request->amount ? floatval($request->amount) : null,
            $request->currency ?? null
        );
    }

    public function updateWish(UpdateWishRequest $request, string $slug): Wish
    {
        $wish = $this->wishRepository->getWishBySlugAndUserId($slug, Auth::id());

        $updatedFields = [
            'title' => $request->title,
            'description' => $request->description ?? null,
            'url' => $request->url ?? null,
            'image_url' => $request->image_url ?? null,
            'amount' => $request->amount ? $request->float('amount') : null,
            'currency' => $request->currency ?? null,
        ];

        if ($wish->title !== $request->title) {
            $updatedFields['slug'] = $this->getSlugForWish($request->title, $wish->wishlist_id);
        }

        $oldLocalFileName = $wish->local_file_name;
        $isImageChanged = $request->image_url !== $wish->image_url;
        if ($isImageChanged) {
            $updatedFields['local_file_name'] = $request->image_url ? $this->saveImageToLocal($request->image_url) : null;
        }

        $wish->update($updatedFields);

        if ($isImageChanged) {
            $this->deleteLocalImage($oldLocalFileName);
        }

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
        if (! $wishlist instanceof Wishlist) {
            return collect();
        }

        if ($wishlist->is_private && $user->id !== Auth::id()) {
            throw new TryToOpenPrivateWishlist('Wishlist is private');
        }

        return $this->wishRepository->getUserWishesByWishlistId($user->id, $wishlist->id);
    }

    public function deleteWish(Wish $wish): void
    {
        $localFileName = $wish->local_file_name;
        $wish->delete();
        $this->deleteLocalImage($localFileName);
    }

    /**
     * Wishes of a deleted user are removed by the database cascade, so their images are collected beforehand.
     *
     * @return Collection<int, string>
     */
    public function getUserWishImages(User $user): Collection
    {
        return $this->wishRepository->getLocalFileNamesByUserId($user->id);
    }

    /**
     * @param  iterable<string>  $localFileNames
     */
    public function deleteImages(iterable $localFileNames): void
    {
        foreach ($localFileNames as $localFileName) {
            $this->deleteLocalImage($localFileName);
        }
    }

    private function deleteLocalImage(?string $localFileName): void
    {
        if ($localFileName) {
            Storage::disk('public')->delete($localFileName);
        }
    }

    private function saveImageToLocal(string $imageUrl): ?string
    {
        return $this->imageDownloader->download($imageUrl, self::IMAGES_DIRECTORY);
    }

    private function getSlugForWish(string $title, int $wishlistId): string
    {
        if (! ($wish = $this->wishRepository->getWishBySlugAndWishlistId(Str::slug($title), $wishlistId)) instanceof Wish) {
            return Str::slug($title);
        }

        $i = 2;
        while ($wish) {
            $slug = Str::slug($title).'-'.$i;
            $wish = $this->wishRepository->getWishBySlugAndWishlistId($slug, $wishlistId);
            $i++;
        }

        return $slug;
    }
}
