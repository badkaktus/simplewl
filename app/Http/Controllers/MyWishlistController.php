<?php

namespace App\Http\Controllers;

use App\Exceptions\TryToOpenPrivateWishlist;
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
    ) {}

    /**
     * @throws TryToOpenPrivateWishlist
     */
    public function index(): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        $user = Auth::user();
        // Users registered before the sign-up listener may have no default wishlist
        // todo set slug, when custom wishlist was added
        $wishlist = $this->wishlistService->createWishlist($user);
        $wishes = $this->wishService->getWishesByUserAndSlug($user->name, $wishlist->slug);

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
