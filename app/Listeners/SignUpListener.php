<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\WishlistService;
use Illuminate\Auth\Events\Registered;

class SignUpListener
{
    public function __construct(private readonly WishlistService $wishlistService)
    {
        //
    }

    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;
        $this->wishlistService->createWishlist($user);
    }
}
