<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __invoke(Request $request): Collection
    {
        return Currency::select(['id', 'name', 'short_code'])->orderBy('name')->get();
    }
}
