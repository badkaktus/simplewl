<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\Services\WishlistService;
use App\Services\WishService;
use Auth;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishService $wishService,
        private readonly UserService $userService,
    ) {
    }

    public function index(
        string $username,
        ?string $slug = null
    ): View|Application|Factory|\Illuminate\Contracts\Foundation\Application {
        $wishes = $this->wishService->getWishesByUserAndSlug($username, $slug);
        $user = $this->userService->getUserByName($username);
        return view(
            'wishlist.index',
            [
                'wishes' => $wishes,
                'user' => $user,
            ]
        );
    }
}
