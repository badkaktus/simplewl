<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class WishImportControllerTest extends TestCase
{
    private const URL = 'https://shop.test/p/1';

    private const AMAZON_URL = 'https://www.amazon.com/HP-Everyday-Storage-Lightweight-Portable/dp/B0HGFTXXXM/ref=sr_1_1?_encoding=UTF8&content-id=amzn1.sym.2ed5cb2d-6276-4908-8487-054be25673ca&dib=eyJ2IjoiMSJ9.EMU3c_rN9xtKqzC4uyo6yMBSSTkTbt5B3hVVcoJKcYbdEm5ykMsImhEths5k0rVZlw1VI0DnRf1AGfaRKRwpAbNDkWOGrcyT4xq_Gz5EuF9Wd6mUOZQ3tkUd-0XSIVwX7r8r3YcnER5wOrGD4KHSKdNe2OY2Ri4DYEpue5rWquZ0-iVfte87ycKDoXOBUzTscfJ9_SeqYLx1MW_rEQLxaF98hqM-qhfxSbsJqiEm1Lo._s9wxaaZdLyomwTKRVKt0gSuYoEHP-1wtMAasCcH02w&dib_tag=se&keywords=laptops&pd_rd_r=770afd29-6f5d-4ed7-9d98-5f8cf9a3aee5&pd_rd_w=TItsr&pd_rd_wg=FHlWv&qid=1792355520&sr=8-1&th=1';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post('/wish/import', ['url' => self::URL])->assertRedirect('/login');
    }

    public function test_returns_imported_product(): void
    {
        $this->be(User::factory()->create());
        Http::fake([self::URL => Http::response(
            '<html><head><meta property="og:title" content="Mug"></head></html>',
            200,
            ['Content-Type' => 'text/html'],
        )]);

        $this->postJson('/wish/import', ['url' => self::URL])
            ->assertOk()
            ->assertJsonPath('isSuccess', true)
            ->assertJsonPath('data.title', 'Mug');
    }

    public function test_returns_failed_result(): void
    {
        $this->be(User::factory()->create());
        Http::fake([self::URL => Http::response('error', 500)]);

        $this->postJson('/wish/import', ['url' => self::URL])
            ->assertOk()
            ->assertExactJson(['isSuccess' => false, 'errorMessage' => "Couldn't load the page"]);
    }

    #[DataProvider('invalidUrlProvider')]
    public function test_validates_url(array $payload): void
    {
        $this->be(User::factory()->create());
        Http::fake();

        $this->postJson('/wish/import', $payload)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure(['url']);
        Http::assertNothingSent();
    }

    public static function invalidUrlProvider(): array
    {
        return [
            'missing' => [[]],
            'not a url' => [['url' => 'not a url']],
            'ftp scheme' => [['url' => 'ftp://shop.test/p']],
            'too long' => [['url' => 'https://shop.test/'.str_repeat('a', 2048)]],
        ];
    }

    public function test_accepts_long_amazon_url(): void
    {
        $this->be(User::factory()->create());
        Http::fake(['https://www.amazon.com/*' => Http::response('error', 503)]);

        $this->postJson('/wish/import', ['url' => self::AMAZON_URL])
            ->assertOk()
            ->assertJsonPath('isSuccess', false);
    }

    public function test_is_rate_limited(): void
    {
        $this->be(User::factory()->create());
        Http::fake([self::URL => Http::response('error', 500)]);

        for ($i = 0; $i < RouteServiceProvider::WISH_IMPORT_PER_MINUTE; $i++) {
            $this->postJson('/wish/import', ['url' => self::URL])->assertOk();
        }

        $this->postJson('/wish/import', ['url' => self::URL])->assertTooManyRequests();
    }
}
