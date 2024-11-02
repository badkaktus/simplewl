<?php

namespace App\View\Components;

use App\Models\Wish;
use App\Models\Wishlist;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SharePage extends Component
{
    public function __construct(
        public ?Wishlist $wishlist = null,
        public ?Wish $wish = null,
        public bool $isShowIcon = true
    ) {
        //        var_dump($this->wish->isClean(), $this->wishlist);
    }

    public function render(): View|Closure|string
    {
        return view('components.share-page');
    }
}
