<?php

namespace App\Http\Controllers;

use App\Services\WishService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;

class WishlistController extends Controller
{
    public function __construct(private readonly WishService $wishService)
    {
    }

    public function index(?string $wishlistId = null
    ): View|Application|Factory|\Illuminate\Contracts\Foundation\Application {
        $wishes = $this->wishService->getWishes($wishlistId);
        return view(
            'wishlist.index',
            [
                'wishes' => $wishes,
            ]
        );
    }
}
