<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\Wish;
use App\Models\Wishlist;
use Tests\TestCase;

class MyWishlistControllerTest extends TestCase
{
    public function test_is_redirect_to_non_auth_user(): void
    {
        $response = $this->get('/my-wishlist');
        $response->assertRedirect('/login');
    }

    public function test_get_my_wishlist_wishes_route(): void
    {
        $user = User::factory()->create();
        $this->be($user);

        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'title' => Wishlist::DEFAULT_WISHLIST_TITLE,
            'slug' => Wishlist::DEFAULT_WISHLIST_SLUG,
        ]);

        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $response = $this->get('/my-wishlist');
        $response->assertStatus(200);
        $response->assertSee($wish->title);
    }

    public function test_my_wishlist_creates_missing_default_wishlist(): void
    {
        $user = User::factory()->create();
        $this->be($user);

        $response = $this->get('/my-wishlist');

        $response->assertOk();
        $response->assertSee('Your wishlist is empty');
        $this->assertDatabaseHas(Wishlist::TABLE_NAME, [
            'user_id' => $user->id,
            'slug' => Wishlist::DEFAULT_WISHLIST_SLUG,
        ]);
    }

    public function test_my_wishlist_uses_default_wishlist_with_another_title(): void
    {
        $user = User::factory()->create();
        $this->be($user);
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'title' => 'Birthday',
            'slug' => Wishlist::DEFAULT_WISHLIST_SLUG,
        ]);

        $response = $this->get('/my-wishlist');

        $response->assertOk();
        $response->assertSee('Birthday');
        $this->assertSame(1, Wishlist::where('user_id', $user->id)->count());
        $this->assertSame($wishlist->id, Wishlist::where('user_id', $user->id)->first()->id);
    }
}
