<?php

use App\Http\Controllers\GithubController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\MyWishlistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\WishController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', static function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/wish', [WishController::class, 'store'])->name('wish.store');
    Route::get('/wish/create', [WishController::class, 'create'])->name('wish.create');
    Route::get('/my-wishlist', [MyWishlistController::class, 'index'])->name('my-wishlist');
    Route::get('/wish/{wish}/edit', [WishController::class, 'edit'])->name('wish.edit');
    Route::put('/wish/{wish}', [WishController::class, 'update'])->name('wish.update');
    Route::delete('/wish/{wish}', [WishController::class, 'destroy'])->name('wish.destroy');
    Route::post('/wish/{slug}/complete', [WishController::class, 'complete'])
        ->name('wish.complete');
});

Route::get('/wishlist/{name}/{slug?}', [WishlistController::class, 'index'])->name('wishlist.index');
Route::get('/wish/{user:name}/{wish}', [WishController::class, 'show'])->name('wish.show');

Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
Route::get('/auth/telegram/callback', [TelegramController::class, 'handleTelegramCallback']);
Route::get('/auth/github', [GithubController::class, 'redirectToGithub']);
Route::get('/auth/github/callback', [GithubController::class, 'handleGithubCallback']);

Route::get('/privacy', static function () {
    return view('privacy');
})->name('privacy');

Route::get('/how-to-delete-your-account', static function () {
    return view('how-to-delete-your-account');
})->name('how-to-delete-your-account');

require __DIR__.'/auth.php';
