<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\Wish;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WishControllerTest extends TestCase
{
    public function test_wish_create_render(): void
    {
        $user = User::factory()->create();
        $this->be($user);
        $response = $this->get('/wish/create');

        $response->assertStatus(200);
        $response->assertSee('New wish');
    }

    public function test_wish_store_successfully(): void
    {
        $user = User::factory()->create();
        $this->be($user);

        Storage::fake('public');

        $title = fake()->words(5, true);
        $description = fake()->paragraph(2);
        $url = fake()->url;
        $imageUrl = fake()->imageUrl();

        $response = $this->post('/wish', [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'image_url' => $imageUrl,
            'amount' => 100,
            'currency' => 'EUR',
        ]);

        $wish = Wish::where('title', $title)->first();

        $this->assertNotNull($wish);
        $this->assertInstanceOf(Wish::class, $wish);
        $this->assertSame($description, $wish->description);
        $this->assertSame($url, $wish->url);
        $this->assertSame($imageUrl, $wish->image_url);
        $this->assertSame(100.00, $wish->getAmount());
        $this->assertSame('EUR', $wish->currency);
        Storage::disk('public')->assertExists($wish->local_file_name);

        $response->assertStatus(302);
        $response->assertRedirect(sprintf('/wishlist/%s/%s', rawurlencode($user->name), $wish->wishlist->slug));
    }

    public function test_show_wish_successfully(): void
    {
        $user = User::factory()->create();
        $this->be($user);

        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $response = $this->get('/wish/'.$user->name.'/'.$wish->slug);

        $response->assertStatus(200);
        $response->assertSee($wish->title);
        $response->assertSee($wish->description);
        $response->assertSee($wish->url);
        $response->assertSee($wish->image_url);
        $response->assertSee($wish->amount);
        $response->assertSee($wish->currency);
    }

    public function test_forbidden_to_show_wish_from_private_wishlist_to_non_auth_user(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'is_private' => 1,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $response = $this->get('/wish/'.$user->name.'/'.$wish->slug);

        $response->assertStatus(403);
        $response->assertSee('private wish list');
    }

    public function test_show_edit_form_successfully(): void
    {
        $user = User::factory()->create();
        $this->be($user);

        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $response = $this->get('/wish/'.$wish->slug.'/edit');

        $response->assertStatus(200);
        $response->assertSee($wish->title);
        $response->assertSee($wish->description);
        $response->assertSee($wish->url);
        $response->assertSee($wish->image_url);
        $response->assertSee($wish->amount);
        $response->assertSee($wish->currency);
    }

    public function test_update_wish_successfully(): void
    {
        $user = User::factory()->create();
        $this->be($user);

        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $title = fake()->words(5, true);
        $description = fake()->paragraph(2);
        $url = fake()->url;
        $imageUrl = fake()->imageUrl();

        $response = $this->put('/wish/'.$wish->slug, [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'image_url' => $imageUrl,
            'amount' => 1986,
            'currency' => 'RUB',
        ]);

        $wish->refresh();

        $this->assertSame($title, $wish->title);
        $this->assertSame($description, $wish->description);
        $this->assertSame($url, $wish->url);
        $this->assertSame($imageUrl, $wish->image_url);
        $this->assertSame(1986.00, $wish->getAmount());
        $this->assertSame('RUB', $wish->currency);

        $response->assertStatus(302);
        $response->assertRedirect(sprintf('/wish/%s/%s', rawurlencode($user->name), $wish->slug));
    }

    public function test_wish_complete_status_successfully_changed(): void
    {
        $user = User::factory()->create();
        $this->be($user);

        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $response = $this->post('/wish/'.$wish->slug.'/complete');

        $wish->refresh();

        $this->assertSame(1, $wish->is_completed);

        $response->assertStatus(200);
        $response->assertExactJson(['isSuccess' => true]);
    }

    public function test_wish_delete_successfully(): void
    {
        $user = User::factory()->create();
        $this->be($user);

        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $response = $this->delete('/wish/'.$wish->slug);

        $this->assertNull(Wish::find($wish->id));

        $response->assertStatus(302);
        $response->assertRedirect(sprintf('/wishlist/%s/%s', rawurlencode($user->name), $wishlist->slug));
    }

    public function test_cant_finalize_a_wish_that_isnt_your_own(): void
    {
        $user = User::factory()->create();
        $user1 = User::factory()->create();
        $this->be($user1);

        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $response = $this->post('/wish/'.$wish->slug.'/complete');

        $wish->refresh();

        $this->assertSame(0, $wish->is_completed);
    }

    public function test_cant_update_a_wish_that_isnt_your_own(): void
    {
        $user = User::factory()->create();
        $user1 = User::factory()->create();
        $this->be($user1);

        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $title = fake()->words(5, true);
        $description = fake()->paragraph(2);
        $url = fake()->url;
        $imageUrl = fake()->imageUrl();

        $response = $this->put('/wish/'.$wish->slug, [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'image_url' => $imageUrl,
            'amount' => 1986,
            'currency' => $this->getNewCurrency($wish->currency),
        ]);

        $wish->refresh();

        $this->assertNotSame($title, $wish->title);
        $this->assertNotSame($description, $wish->description);
        $this->assertNotSame($url, $wish->url);
        $this->assertNotSame($imageUrl, $wish->image_url);
        $this->assertNotSame('1986.00', $wish->amount);
        $this->assertNotSame('RUB', $wish->currency);
    }

    public function test_cant_destroy_a_wish_that_isnt_your_own(): void
    {
        $user = User::factory()->create();
        $user1 = User::factory()->create();
        $this->be($user1);

        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $response = $this->delete('/wish/'.$wish->slug);

        $this->assertNotNull(Wish::find($wish->id));
    }

    public function test_auth_user_can_see_wish_from_another_user_public_wishlist(): void
    {
        $user = User::factory()->create();
        $user1 = User::factory()->create();
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'is_private' => 0,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $this->be($user1);
        $response = $this->get('/wish/'.$user->name.'/'.$wish->slug);

        $response->assertStatus(200);
        $response->assertSee($wish->title);
        $response->assertSee($wish->description);
        $response->assertSee($wish->url);
        $response->assertSee($wish->image_url);
        $response->assertSee($wish->amount);
        $response->assertSee($wish->currency);
    }

    public function test_non_auth_user_can_see_wish_from_another_user_public_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'is_private' => 0,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $response = $this->get('/wish/'.$user->name.'/'.$wish->slug);

        $response->assertStatus(200);
        $response->assertSee($wish->title);
        $response->assertSee($wish->description);
        $response->assertSee($wish->url);
        $response->assertSee($wish->image_url);
        $response->assertSee($wish->amount);
        $response->assertSee($wish->currency);
    }

    private function getNewCurrency(string $currency): string
    {
        if ($currency === 'RUB') {
            return 'USD';
        }

        return 'RUB';
    }
}
