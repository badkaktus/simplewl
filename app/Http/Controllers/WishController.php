<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWishRequest;
use App\Http\Requests\UpdateWishRequest;
use App\Models\Wish;
use App\Services\WishService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class WishController extends Controller
{
    public function __construct(private readonly WishService $wishService)
    {
    }

    public function create(): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        return view('wish.create');
    }

    public function store(StoreWishRequest $request): RedirectResponse
    {
        $wish = $this->wishService->createWish($request);
        return Redirect::route('wishlist.index', ['wishlistId' => $wish->wishlist_id]);
    }

    public function show(Wish $wish): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        return view('wish.show', ['wish' => $wish]);
    }

    public function edit(Wish $wish): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        return view('wish.edit', ['wish' => $wish]);
    }

    public function update(UpdateWishRequest $request, Wish $wish): RedirectResponse
    {
        $updatedWish = $this->wishService->updateWish($request, $wish->id);
        return Redirect::route('wish.show', ['wish' => $updatedWish]);
    }

    public function complete(string $wishSlug): RedirectResponse
    {
        $wish = $this->wishService->changeCompletedStatus($wishSlug);
        return Redirect::route('wish.show', ['wish' => $wish]);
    }

    public function destroy(Wish $wish): RedirectResponse
    {
        $this->wishService->deleteWish($wish);
        return Redirect::route('wishlist.index');
    }
}
