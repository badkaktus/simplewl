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
}
