<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SharePage extends Component
{
    public function __construct(public bool $isShowIcon = true)
    {
        //
    }

    public function render(): View|Closure|string
    {
        return view('components.share-page');
    }
}
