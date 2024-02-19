<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyWishRequest;
use App\Http\Requests\ShowWishRequest;
use App\Http\Requests\StoreWishRequest;
use App\Http\Requests\UpdateWishRequest;
use App\Models\User;
use App\Models\Wish;
use App\Services\WishService;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
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

    /**
     * @throws Exception
     */
    public function store(StoreWishRequest $request): RedirectResponse
    {
        $wish = $this->wishService->createWish($request);

        return Redirect::route(
            'wishlist.index',
            [
                'name' => $wish->wishlist->user->name,
                'slug' => $wish->wishlist->slug,
            ]
        );
    }

    public function show(ShowWishRequest $request, User $user, Wish $wish): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        return view('wish.show', ['wish' => $wish]);
    }

    public function edit(Wish $wish): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        return view('wish.edit', ['wish' => $wish]);
    }

    public function update(UpdateWishRequest $request, Wish $wish): RedirectResponse
    {
        $updatedWish = $this->wishService->updateWish($request, $wish->slug);

        return Redirect::route('wish.show', [
            'user' => $wish->wishlist->user->name,
            'wish' => $updatedWish,
        ]);
    }

    public function complete(string $wishSlug): JsonResponse
    {
        $this->wishService->changeCompletedStatus($wishSlug);

        return response()->json(['isSuccess' => true]);

    }

    public function destroy(DestroyWishRequest $request, Wish $wish): RedirectResponse
    {
        $username = $wish->wishlist->user->name;
        $wishlistSlug = $wish->wishlist->slug;
        $this->wishService->deleteWish($wish);

        return Redirect::route('wishlist.index', [
            'name' => $username,
            'slug' => $wishlistSlug,
        ]);
    }
}
