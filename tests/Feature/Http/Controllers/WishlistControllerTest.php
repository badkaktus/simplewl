<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\Wishlist;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\TestCase;

class WishlistControllerTest extends TestCase
{
    public function test_get_route_user_default_wishlist(): void
    {
        $wishlist = Wishlist::factory()->create([
            'slug' => Wishlist::DEFAULT_WISHLIST_SLUG,
        ]);
        $response = $this->get('/wishlist/'.$wishlist->user->name);

        $response->assertStatus(200);
        $response->assertSee('Your wishlist is empty');
    }

    public function test_wishlist_of_unknown_user_returns_not_found(): void
    {
        $this->get('/wishlist/unknown-user')->assertNotFound();
        $this->get('/wishlist/unknown-user/'.Wishlist::DEFAULT_WISHLIST_SLUG)->assertNotFound();
    }

    public function test_unknown_wishlist_of_existing_user_returns_not_found(): void
    {
        $user = User::factory()->create();

        $this->get('/wishlist/'.$user->name)->assertNotFound();
        $this->get('/wishlist/'.$user->name.'/unknown-wishlist')->assertNotFound();
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

    public function test_user_change_his_own_wishlist_visibility(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'is_private' => 1,
        ]);
        $this->be($user);

        $response = $this->post('/wishlist/'.$user->name.'/'.$wishlist->slug.'/visibility');

        $response->assertStatus(ResponseAlias::HTTP_OK);
        $response->assertJson(['success' => true, 'isPrivate' => false]);
        $this->assertDatabaseHas(Wishlist::TABLE_NAME, [
            'user_id' => $user->id,
            'title' => $wishlist->title,
            'slug' => $wishlist->slug,
            'is_private' => 0,
        ]);
    }

    public function test_user_cant_change_not_his_own_wishlist_visibility(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->create([
            'is_private' => 0,
        ]);
        $this->be($user);

        $response = $this->post('/wishlist/'.$user->name.'/'.$wishlist->slug.'/visibility');

        $response->assertStatus(ResponseAlias::HTTP_FORBIDDEN);
        $this->assertDatabaseHas(Wishlist::TABLE_NAME, [
            'user_id' => $wishlist->user_id,
            'title' => $wishlist->title,
            'slug' => $wishlist->slug,
            'is_private' => 0,
        ]);
    }

    public function test_user_cant_change_another_user_wishlist_visibility_with_same_slug(): void
    {
        $user = User::factory()->create();
        Wishlist::factory()->create([
            'user_id' => $user->id,
            'slug' => Wishlist::DEFAULT_WISHLIST_SLUG,
            'is_private' => 0,
        ]);
        $victim = User::factory()->create();
        $victimWishlist = Wishlist::factory()->create([
            'user_id' => $victim->id,
            'slug' => Wishlist::DEFAULT_WISHLIST_SLUG,
            'is_private' => 1,
        ]);
        $this->be($user);

        $response = $this->post('/wishlist/'.$victim->name.'/'.$victimWishlist->slug.'/visibility');

        $response->assertStatus(ResponseAlias::HTTP_FORBIDDEN);
        $this->assertDatabaseHas(Wishlist::TABLE_NAME, [
            'id' => $victimWishlist->id,
            'is_private' => 1,
        ]);
        $this->assertDatabaseHas(Wishlist::TABLE_NAME, [
            'user_id' => $user->id,
            'is_private' => 0,
        ]);
    }

    public function test_guest_cant_change_wishlist_visibility(): void
    {
        $wishlist = Wishlist::factory()->create([
            'is_private' => 1,
        ]);

        $response = $this->post('/wishlist/'.$wishlist->user->name.'/'.$wishlist->slug.'/visibility');

        $response->assertRedirect('/login');
        $this->assertDatabaseHas(Wishlist::TABLE_NAME, [
            'id' => $wishlist->id,
            'is_private' => 1,
        ]);
    }

    public function test_auth_user_can_see_wishlist_from_another_user_public_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'is_private' => 0,
        ]);
        $userAuth = User::factory()->create();
        $this->be($userAuth);

        $response = $this->get('/wishlist/'.$user->name.'/'.$wishlist->slug);

        $response->assertStatus(200);
    }

    public function test_non_auth_user_can_see_wishlist_from_another_user_public_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'is_private' => 0,
        ]);

        $response = $this->get('/wishlist/'.$user->name.'/'.$wishlist->slug);

        $response->assertStatus(200);
    }
}
