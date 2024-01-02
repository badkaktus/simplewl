<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\Wishlist;
use Tests\TestCase;

class WishlistControllerTest extends TestCase
{
    public function test_get_route_user_default_wishlist(): void
    {
        $user = User::factory()->create();
        $response = $this->get('/wishlist/'.$user->name);

        $response->assertStatus(200);
    }

    public function test_get_route_user_and_slug(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
        ]);
        $response = $this->get('/wishlist/'.$user->name.'/'.$wishlist->slug);

        $response->assertStatus(200);
        $this->assertDatabaseHas(Wishlist::TABLE_NAME, [
            'user_id' => $user->id,
            'title' => $wishlist->title,
            'slug' => $wishlist->slug,
        ]);
    }

    public function test_non_auth_user_try_to_get_private_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'is_private' => 1,
        ]);

        $response = $this->get('/wishlist/'.$user->name.'/'.$wishlist->slug);

        $response->assertStatus(403);
        $response->assertSee('private wish list');
    }

    public function test_auth_user_try_to_get_someone_else_private_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'is_private' => 1,
        ]);
        $userAuth = User::factory()->create();
        $this->be($userAuth);

        $response = $this->get('/wishlist/'.$user->name.'/'.$wishlist->slug);

        $response->assertStatus(403);
        $response->assertSee('private wish list');
    }

    public function test_auth_user_try_to_get_his_own_private_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'is_private' => 1,
        ]);
        $this->be($user);

        $response = $this->get('/wishlist/'.$user->name.'/'.$wishlist->slug);

        $response->assertStatus(200);
    }
}
