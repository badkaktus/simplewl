<?php

namespace App\Http\Controllers;

use App\Exceptions\TryToOpenPrivateWishlist;
use App\Models\Wishlist;
use App\Services\WishlistService;
use App\Services\WishService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;

class MyWishlistController extends Controller
{
    public function __construct(
        private readonly WishService $wishService,
        private readonly WishlistService $wishlistService,
    ) {
    }

    /**
     * @throws TryToOpenPrivateWishlist
     */
    public function index(): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        $user = Auth::user();
        // todo set slug, when custom wishlist was added
        $wishes = $this->wishService->getWishesByUserAndSlug($user->name, null);
        $wishlist = $this->wishlistService->getWishlistByUserIdAndSlug($user->id, Wishlist::DEFAULT_WISHLIST_SLUG);

        return view(
            'wishlist.index',
            [
                'wishes' => $wishes,
                'user' => $user,
                'wishlist' => $wishlist,
            ]
        );
    }
}
