<?php

namespace App\Listeners;

use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SignUpListener
{
    /**
     * Create the event listener.
     */
    public function __construct(private \App\Services\WishlistService $wishlistService)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;
        $this->wishlistService->createWishlist($user);
    }
}
