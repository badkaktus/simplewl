<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\StoreWishRequest;
use App\Models\User;
use App\Models\Wish;
use App\Models\Wishlist;
use App\Services\WishService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WishServiceTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('wishDataProvider')]
    public function test_create_wish_from_request(
        string $title,
        ?string $description,
        ?string $url,
        ?string $image_url,
        ?float $amount,
        ?string $currency
    ): void {
        $imageContent = 'fake_image_content';
        Http::fake([
            $image_url => Http::response($imageContent, 200, ['Content-Type' => 'image/jpeg']),
        ]);
        Storage::fake('public');

        $storeWishRequest = StoreWishRequest::create('/wishes', 'POST', [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'image_url' => $image_url,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        $user = User::factory()->create();
        $this->be($user);

        $wish = app(WishService::class)->createWish($storeWishRequest);

        $this->assertDatabaseHas('wishes', [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'image_url' => $image_url,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        Storage::disk('public')->assertExists($wish->local_file_name);
    }

    public function test_check_increment_slug(): void
    {
        $wishlist = Wishlist::factory()->create([
            'title' => Wishlist::DEFAULT_WISHLIST_TITLE,
            'slug' => Wishlist::DEFAULT_WISHLIST_SLUG,
        ]);
        $this->be($wishlist->user);

        $title = fake()->words(3, true);

        Wish::factory()->create([
            'title' => $title,
            'slug' => Str::slug($title),
            'wishlist_id' => $wishlist->id,
        ]);

        Wish::factory()->create([
            'title' => $title,
            'slug' => Str::slug($title).'-2',
            'wishlist_id' => $wishlist->id,
        ]);

        $storeWishRequest = StoreWishRequest::create('/wishes', 'POST', [
            'title' => $title,
            'description' => fake()->text,
            'amount' => fake()->randomFloat(2, 1, 1000),
            'currency' => fake()->currencyCode,
        ]);

        $wish = app(WishService::class)->createWish($storeWishRequest);

        $this->assertInstanceOf(Wish::class, $wish);
        $this->assertDatabaseHas('wishes', [
            'title' => $title,
            'slug' => Str::slug($title).'-3',
        ]);
    }

    public static function wishDataProvider(): array
    {
        return [
            'wish with full data' => [
                'title' => 'Test wish',
                'description' => 'Test description',
                'url' => 'https://test.com',
                'image_url' => 'https://test.com/image.jpg',
                'amount' => 100.00,
                'currency' => 'USD',
            ],
            'wish with strange image url' => [
                'title' => 'Test wish',
                'description' => 'Test description',
                'url' => 'https://test.com',
                'image_url' => 'https://images.svc.ui.com/?u=https%3A%2F%2Fcdn.ecomm.ui.com%2Fproducts%2F60459473-c989-41db-93f2-3c0f40df84f3%2F55fcd0c8-abc5-4637-9716-6d3fbd32fda7.png&q=75&w=3840',
                'amount' => 100,
                'currency' => 'USD',
            ],
            'wish with just title' => [
                'title' => 'Test wish',
                'description' => null,
                'url' => null,
                'image_url' => null,
                'amount' => null,
                'currency' => null,
            ],
        ];
    }
}
