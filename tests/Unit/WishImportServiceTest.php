<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Currency;
use App\Services\WishImport\WishImportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WishImportServiceTest extends TestCase
{
    private const URL = 'https://shop.test/p/1';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Currency::factory()->create(['short_code' => 'USD']);
    }

    public function test_imports_product(): void
    {
        $this->fakePage($this->productPage('USD'));

        $result = $this->service()->import(self::URL)->toArray();

        $this->assertTrue($result['isSuccess']);
        $this->assertSame('Mug', $result['data']['title']);
        $this->assertSame(456.0, $result['data']['amount']);
        $this->assertSame('USD', $result['data']['currency']);
        Http::assertSent(fn (Request $request): bool => $request->header('User-Agent') === ['facebookexternalhit/1.1']);
    }

    public function test_drops_unknown_currency(): void
    {
        $this->fakePage($this->productPage('XYZ'));

        $result = $this->service()->import(self::URL)->toArray();

        $this->assertNull($result['data']['currency']);
        $this->assertSame(456.0, $result['data']['amount']);
    }

    public function test_fails_when_page_cannot_be_loaded(): void
    {
        Http::fake([self::URL => Http::response('error', 500)]);

        $this->assertSame(
            ['isSuccess' => false, 'errorMessage' => "Couldn't load the page"],
            $this->service()->import(self::URL)->toArray(),
        );
    }

    public function test_fails_for_non_html_content(): void
    {
        $this->fakePage('{"name": "Mug"}', 'application/json');

        $this->assertSame(
            ['isSuccess' => false, 'errorMessage' => "Couldn't load the page"],
            $this->service()->import(self::URL)->toArray(),
        );
    }

    public function test_fails_when_no_product_data(): void
    {
        $this->fakePage('<html><body>You are now in line</body></html>');

        $this->assertSame(
            ['isSuccess' => false, 'errorMessage' => 'No product data found on this page'],
            $this->service()->import(self::URL)->toArray(),
        );
    }

    #[DataProvider('encodingProvider')]
    public function test_converts_page_encoding(string $head, string $contentType): void
    {
        $html = mb_convert_encoding('<html><head>'.$head.'<title>Кружка</title></head></html>', 'Windows-1251', 'UTF-8');
        $this->fakePage($html, $contentType);

        $result = $this->service()->import(self::URL)->toArray();

        $this->assertSame('Кружка', $result['data']['title']);
    }

    public static function encodingProvider(): array
    {
        return [
            'meta charset' => ['<meta charset="windows-1251">', 'text/html'],
            'meta http-equiv' => ['<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">', 'text/html'],
            'content type header' => ['', 'text/html; charset=windows-1251'],
        ];
    }

    public function test_resolves_relative_image_against_final_url(): void
    {
        $this->fakeHostResolver(['shop.test' => [self::PUBLIC_IP], 'cdn.test' => [self::PUBLIC_IP]]);
        Http::fake([
            self::URL => Http::response('', 302, ['Location' => 'https://cdn.test/p']),
            'https://cdn.test/p' => Http::response(
                '<html><head><meta property="og:image" content="/a.jpg"></head></html>',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $result = $this->service()->import(self::URL)->toArray();

        $this->assertSame('https://cdn.test/a.jpg', $result['data']['imageUrl']);
    }

    private function fakePage(string $body, string $contentType = 'text/html; charset=utf-8'): void
    {
        Http::fake([self::URL => Http::response($body, 200, ['Content-Type' => $contentType])]);
    }

    private function productPage(string $currency): string
    {
        $jsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => 'Mug',
            'offers' => ['@type' => 'Offer', 'price' => 456.0, 'priceCurrency' => $currency],
        ]);

        return '<html><head><script type="application/ld+json">'.$jsonLd.'</script></head></html>';
    }

    private function service(): WishImportService
    {
        return resolve(WishImportService::class);
    }
}
