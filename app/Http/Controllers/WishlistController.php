<?php

namespace App\Http\Controllers;

use App\Exceptions\TryToOpenPrivateWishlist;
use App\Http\Requests\ChangeWishlistVisibilityRequest;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\UserService;
use App\Services\WishlistService;
use App\Services\WishService;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishService $wishService,
        private readonly UserService $userService,
        private readonly WishlistService $wishlistService,
    ) {
    }

    /**
     * @throws TryToOpenPrivateWishlist
     * @throws Exception
     */
    public function index(
        string $username,
        ?string $slug = null
    ): View|Application|Factory|\Illuminate\Contracts\Foundation\Application {
        $wishes = $this->wishService->getWishesByUserAndSlug($username, $slug);
        $user = $this->userService->getUserByName($username);
        if (is_null($user)) {
            throw new Exception('User not found');
        }
        if (is_null($slug)) {
            $slug = Wishlist::DEFAULT_WISHLIST_SLUG;
        }
        $wishlist = $this->wishlistService->getWishlistByUserIdAndSlug($user->id, $slug);

        return view(
            'wishlist.index',
            [
                'wishes' => $wishes,
                'user' => $user,
                'wishlist' => $wishlist,
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function changeVisibility(
        ChangeWishlistVisibilityRequest $request,
        string $username,
        string $slug
    ): RedirectResponse {
        $user = $this->userService->getUserByName($username);
        if (is_null($user)) {
            throw new Exception('User not found');
        }
        $wishlist = $this->wishlistService->getWishlistByUserIdAndSlug($user->id, $slug);
        $this->wishlistService->changeVisibility($wishlist);
        return redirect()->route('wishlist.index', [$username, $slug]);
    }
}
