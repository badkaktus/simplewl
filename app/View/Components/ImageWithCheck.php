<?php

namespace App\View\Components;

use App\Models\Wish;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ImageWithCheck extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public Wish $wish,
        public string $class = '',
        public bool $isShowText = true,
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.image-with-check');
    }
}
