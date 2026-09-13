<?php

namespace App\Http\Controllers;

use App\Exceptions\TryToOpenPrivateWishlist;
use App\Http\Requests\ChangeWishlistVisibilityRequest;
use App\Models\Wishlist;
use App\Services\UserService;
use App\Services\WishlistService;
use App\Services\WishService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishService $wishService,
        private readonly UserService $userService,
        private readonly WishlistService $wishlistService,
    ) {}

    /**
     * @throws TryToOpenPrivateWishlist
     */
    public function index(
        string $username,
        ?string $slug = null
    ): View|Application|Factory|\Illuminate\Contracts\Foundation\Application {
        $user = $this->userService->getUserByName($username);
        if (is_null($user)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $wishlist = $this->wishlistService->getWishlistByUserIdAndSlug($user->id, $slug ?? Wishlist::DEFAULT_WISHLIST_SLUG);
        if (is_null($wishlist)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $wishes = $this->wishService->getWishesByUserAndSlug($username, $slug);

        return view(
            'wishlist.index',
            [
                'wishes' => $wishes,
                'user' => $user,
                'wishlist' => $wishlist,
            ]
        );
    }

    public function changeVisibility(
        ChangeWishlistVisibilityRequest $request,
        string $username,
        string $slug
    ): JsonResponse {
        $wishlist = $this->wishlistService->getWishlistByUserIdAndSlug($request->user()->id, $slug);
        $updatedWishlist = $this->wishlistService->changeVisibility($wishlist);

        return response()->json(['success' => true, 'isPrivate' => $updatedWishlist->is_private]);
    }
}
