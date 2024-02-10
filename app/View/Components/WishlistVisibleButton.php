<?php

namespace App\View\Components;

use App\Models\User;
use App\Models\Wishlist;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class WishlistVisibleButton extends Component
{
    public function __construct(public User $user, public Wishlist $wishlist)
    {
    }

    public function render(): View|Closure|string
    {
        return view('components.wishlist-visible-button');
    }
}
