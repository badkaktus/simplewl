<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CurrencySelect extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $selectedCurrency = null,
    ) {
        $this->selectedCurrency = isset($selectedCurrency) ? strtoupper($this->selectedCurrency) : '';
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.currency-select');
    }
}
