<?php

namespace App\Http\Controllers;

use App\Services\WishService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;

class MyWishlistController extends Controller
{
    public function __construct(private readonly WishService $wishService)
    {
    }

    public function index(): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        $user = Auth::user();
        $wishes = $this->wishService->getWishesByUserAndSlug($user->name, null);

        return view(
            'wishlist.index',
            [
                'wishes' => $wishes,
                'user' => $user,
            ]
        );
    }
}
