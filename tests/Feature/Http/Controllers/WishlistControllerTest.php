<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WishlistControllerTest extends TestCase
{
        public function test_get_route_user_default_wishlist(): void
        {
            $user = User::factory()->create();
            $response = $this->get('/wishlist/' . $user->name);

            $response->assertStatus(200);
        }

        public function test_get_route_user_and_slug(): void
        {
            $user = User::factory()->create();
            $wishlist = Wishlist::factory()->create([
                'user_id' => $user->id,
            ]);
            $response = $this->get('/wishlist/' . $user->name . '/' . $wishlist->slug);

            $response->assertStatus(200);
            $this->assertDatabaseHas(Wishlist::TABLE_NAME, [
                'user_id' => $user->id,
                'title' => $wishlist->title,
                'slug' => $wishlist->slug,
            ]);
        }
}
