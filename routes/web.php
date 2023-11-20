<?php

use App\Http\Controllers\MyWishlistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WishController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

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
});


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/wish', [WishController::class, 'store'])->name('wish.store');
    Route::get('/wish/create', [WishController::class, 'create'])->name('wish.create');
    Route::get('/wish/{wish}/edit', [WishController::class, 'edit'])->name('wish.edit');
    Route::put('/wish/{wish}', [WishController::class, 'update'])->name('wish.update');
    Route::delete('/wish/{wish}', [WishController::class, 'destroy'])->name('wish.destroy');
    Route::post('wish/{id}/complete', [WishController::class, 'complete'])->name('wish.complete');
    Route::get('/my-wishlist', [MyWishlistController::class, 'index'])->name('my-wishlist');
    Route::post('/wish/{slug}/complete', [WishController::class, 'complete'])->name('wish.complete');
});
Route::get('/wishlist/{name}/{slug?}', [WishlistController::class, 'index'])->name('wishlist.index');
Route::get('/wish/{user:name}/{wish}', [WishController::class, 'show'])->name('wish.show');

require __DIR__ . '/auth.php';
