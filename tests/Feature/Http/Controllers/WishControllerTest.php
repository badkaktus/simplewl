<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\Wish;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $imageUrl = fake()->imageUrl();
        Http::fake([
            $imageUrl => Http::response($this->pngImage(), 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $title = fake()->words(5, true);
        $description = fake()->paragraph(2);
        $url = fake()->url;

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

        $response = $this->get('/wish/'.$user->name.'/'.$wish->slug.'/edit');

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
        Http::fake([
            $imageUrl => Http::response($this->pngImage(), 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $response = $this->put('/wish/'.$user->name.'/'.$wish->slug, [
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

        $response = $this->delete('/wish/'.$user->name.'/'.$wish->slug);

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

        $this->post('/wish/'.$wish->slug.'/complete');

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

        $this->put('/wish/'.$user->name.'/'.$wish->slug, [
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

        $this->delete('/wish/'.$user->name.'/'.$wish->slug);

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

    public function test_cant_see_edit_form_of_a_wish_that_isnt_your_own(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'is_private' => 1,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
        ]);

        $this->be(User::factory()->create());
        $response = $this->get('/wish/'.$user->name.'/'.$wish->slug.'/edit');

        $response->assertForbidden();
        $response->assertDontSee($wish->title);
    }

    public function test_guest_cant_see_edit_form(): void
    {
        $wish = Wish::factory()->create();

        $response = $this->get('/wish/'.$wish->wishlist->user->name.'/'.$wish->slug.'/edit');

        $response->assertRedirect('/login');
    }

    public function test_show_resolves_wish_with_same_slug_by_owner(): void
    {
        [$firstWish, $secondWish] = $this->createWishesWithSameSlug();

        $this->get('/wish/'.$firstWish->wishlist->user->name.'/'.$firstWish->slug)
            ->assertOk()
            ->assertSee($firstWish->description)
            ->assertDontSee($secondWish->description);

        $this->get('/wish/'.$secondWish->wishlist->user->name.'/'.$secondWish->slug)
            ->assertOk()
            ->assertSee($secondWish->description)
            ->assertDontSee($firstWish->description);
    }

    public function test_show_returns_not_found_when_wish_belongs_to_another_user(): void
    {
        $wish = Wish::factory()->create();
        $anotherUser = User::factory()->create();

        $response = $this->get('/wish/'.$anotherUser->name.'/'.$wish->slug);

        $response->assertNotFound();
    }

    public function test_owner_can_edit_update_and_delete_wish_with_slug_used_by_another_user(): void
    {
        [$firstWish, $secondWish] = $this->createWishesWithSameSlug();
        $owner = $secondWish->wishlist->user;
        $this->be($owner);
        $url = '/wish/'.$owner->name.'/'.$secondWish->slug;

        $this->get($url.'/edit')
            ->assertOk()
            ->assertSee($secondWish->description);

        $this->put($url, [
            'title' => $secondWish->title,
            'description' => 'updated description',
        ])->assertRedirect();

        $this->assertSame('updated description', $secondWish->refresh()->description);
        $this->assertNotSame('updated description', $firstWish->refresh()->description);

        $this->delete($url)->assertRedirect();

        $this->assertNull(Wish::find($secondWish->id));
        $this->assertNotNull(Wish::find($firstWish->id));
    }

    public function test_cant_update_another_user_wish_through_own_name_in_url(): void
    {
        [$firstWish, $secondWish] = $this->createWishesWithSameSlug();
        $secondOwner = $secondWish->wishlist->user;
        $secondWish->delete();
        $this->be($secondOwner);

        $response = $this->put('/wish/'.$secondOwner->name.'/'.$firstWish->slug, [
            'title' => 'hacked',
        ]);

        $response->assertNotFound();
        $this->assertNotSame('hacked', $firstWish->refresh()->title);
    }

    public function test_wish_store_does_not_download_image_from_internal_host(): void
    {
        $user = User::factory()->create();
        $this->be($user);
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake();
        $this->fakeHostResolver(['169.254.169.254']);

        $this->post('/wish', [
            'title' => 'Metadata',
            'image_url' => 'http://metadata.test/latest/meta-data',
        ])->assertRedirect();

        $wish = Wish::where('title', 'Metadata')->first();

        $this->assertNull($wish->local_file_name);
        $this->assertSame([], Storage::disk('public')->allFiles());
        Http::assertNothingSent();
    }

    #[DataProvider('notHttpUrlProvider')]
    public function test_wish_store_rejects_not_http_urls(string $field, string $value): void
    {
        $this->be(User::factory()->create());
        Http::fake();

        $response = $this->post('/wish', [
            'title' => 'Wish with bad url',
            $field => $value,
        ]);

        $response->assertSessionHasErrors($field);
        $this->assertNull(Wish::where('title', 'Wish with bad url')->first());
        Http::assertNothingSent();
    }

    public static function notHttpUrlProvider(): array
    {
        return [
            'file image url' => ['image_url', 'file:///etc/passwd'],
            'gopher image url' => ['image_url', 'gopher://localhost:6379/_INFO'],
            'javascript url' => ['url', 'javascript://example.com/%0Aalert(1)'],
        ];
    }

    public function test_update_wish_with_cleared_image_url_deletes_old_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('wishes/old.png', $this->pngImage());
        [$user, $wish] = $this->createOwnWish(['local_file_name' => 'wishes/old.png']);

        $response = $this->put('/wish/'.$user->name.'/'.$wish->slug, [
            'title' => $wish->title,
            'image_url' => null,
        ]);

        $response->assertRedirect();
        $wish->refresh();
        $this->assertNull($wish->image_url);
        $this->assertNull($wish->local_file_name);
        Storage::disk('public')->assertMissing('wishes/old.png');
    }

    public function test_update_wish_with_new_image_url_replaces_old_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('wishes/old.png', $this->pngImage());
        [$user, $wish] = $this->createOwnWish(['local_file_name' => 'wishes/old.png']);
        $newImageUrl = 'https://shop.test/new.png';
        Http::fake([
            $newImageUrl => Http::response($this->pngImage(), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->put('/wish/'.$user->name.'/'.$wish->slug, [
            'title' => $wish->title,
            'image_url' => $newImageUrl,
        ])->assertRedirect();

        $wish->refresh();
        $this->assertNotNull($wish->local_file_name);
        $this->assertNotSame('wishes/old.png', $wish->local_file_name);
        Storage::disk('public')->assertExists($wish->local_file_name);
        Storage::disk('public')->assertMissing('wishes/old.png');
    }

    public function test_update_wish_with_same_image_url_keeps_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('wishes/old.png', $this->pngImage());
        [$user, $wish] = $this->createOwnWish(['local_file_name' => 'wishes/old.png']);
        Http::fake();

        $this->put('/wish/'.$user->name.'/'.$wish->slug, [
            'title' => 'New title',
            'image_url' => $wish->image_url,
        ])->assertRedirect();

        $this->assertSame('wishes/old.png', $wish->refresh()->local_file_name);
        Storage::disk('public')->assertExists('wishes/old.png');
        Http::assertNothingSent();
    }

    public function test_wish_delete_deletes_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('wishes/old.png', $this->pngImage());
        [$user, $wish] = $this->createOwnWish(['local_file_name' => 'wishes/old.png']);

        $this->delete('/wish/'.$user->name.'/'.$wish->slug)->assertRedirect();

        $this->assertNull(Wish::find($wish->id));
        Storage::disk('public')->assertMissing('wishes/old.png');
    }

    #[DataProvider('invalidCurrencyProvider')]
    public function test_wish_store_rejects_invalid_currency(string $currency): void
    {
        $this->be(User::factory()->create());

        $response = $this->post('/wish', [
            'title' => 'Wish with bad currency',
            'currency' => $currency,
        ]);

        $response->assertSessionHasErrors('currency');
        $this->assertNull(Wish::where('title', 'Wish with bad currency')->first());
    }

    public static function invalidCurrencyProvider(): array
    {
        return [
            'zero from old currency select' => ['0'],
            'too short' => ['US'],
            'too long' => ['DOLLAR'],
            'not letters' => ['U$D'],
        ];
    }

    public function test_wish_store_without_currency_saves_null(): void
    {
        $this->be(User::factory()->create());

        $this->post('/wish', [
            'title' => 'Wish without currency',
            'currency' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull(Wish::where('title', 'Wish without currency')->first()->currency);
    }

    /**
     * @return array{User, Wish}
     */
    private function createOwnWish(array $attributes = []): array
    {
        $user = User::factory()->create();
        $this->be($user);
        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
        ]);
        $wish = Wish::factory()->create([
            'wishlist_id' => $wishlist->id,
            ...$attributes,
        ]);

        return [$user, $wish];
    }

    /**
     * @return array{Wish, Wish}
     */
    private function createWishesWithSameSlug(): array
    {
        $title = fake()->words(3, true);

        $firstWish = Wish::factory()->create([
            'title' => $title,
            'slug' => Str::slug($title),
        ]);
        $secondWish = Wish::factory()->create([
            'title' => $title,
            'slug' => Str::slug($title),
        ]);

        return [$firstWish, $secondWish];
    }

    private function getNewCurrency(string $currency): string
    {
        if ($currency === 'RUB') {
            return 'USD';
        }

        return 'RUB';
    }
}
