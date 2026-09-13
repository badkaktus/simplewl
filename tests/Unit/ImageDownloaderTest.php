<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ImageDownloader;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImageDownloaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Http::preventStrayRequests();
    }

    public function test_downloads_image_from_public_host(): void
    {
        Http::fake([
            'https://shop.test/image' => Http::response($this->pngImage(), 200, ['Content-Type' => 'image/png']),
        ]);

        $path = $this->downloader()->download('https://shop.test/image', 'wishes');

        $this->assertNotNull($path);
        $this->assertStringStartsWith('wishes/', $path);
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists($path);
    }

    #[DataProvider('internalIpProvider')]
    public function test_does_not_request_internal_hosts(string $ip): void
    {
        $this->fakeHostResolver([$ip]);
        Http::fake();

        $path = $this->downloader()->download('http://internal.test/latest/meta-data', 'wishes');

        $this->assertNull($path);
        Http::assertNothingSent();
    }

    public static function internalIpProvider(): array
    {
        return [
            'loopback' => ['127.0.0.1'],
            'private network' => ['10.0.0.1'],
            'cloud metadata' => ['169.254.169.254'],
            'carrier-grade NAT' => ['100.64.0.1'],
            'unspecified' => ['0.0.0.0'],
            'IPv6 loopback' => ['::1'],
            'IPv6 unique local' => ['fd00::1'],
            'IPv4-mapped IPv6' => ['::ffff:127.0.0.1'],
            'NAT64' => ['64:ff9b::7f00:1'],
            '6to4' => ['2002:7f00:1::1'],
        ];
    }

    public function test_does_not_request_host_with_any_internal_ip(): void
    {
        $this->fakeHostResolver([self::PUBLIC_IP, '127.0.0.1']);
        Http::fake();

        $this->assertNull($this->downloader()->download('https://mixed.test/image', 'wishes'));
        Http::assertNothingSent();
    }

    public function test_does_not_request_unresolvable_host(): void
    {
        $this->fakeHostResolver([]);
        Http::fake();

        $this->assertNull($this->downloader()->download('https://unknown.test/image', 'wishes'));
        Http::assertNothingSent();
    }

    #[DataProvider('unsupportedUrlProvider')]
    public function test_does_not_request_unsupported_urls(string $url): void
    {
        Http::fake();

        $this->assertNull($this->downloader()->download($url, 'wishes'));
        Http::assertNothingSent();
    }

    public static function unsupportedUrlProvider(): array
    {
        return [
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://shop.test/image.png'],
            'gopher scheme' => ['gopher://shop.test:6379/_INFO'],
            'without host' => ['http:///image.png'],
        ];
    }

    #[DataProvider('notImageProvider')]
    public function test_does_not_store_content_that_is_not_image(string $body, string $contentType): void
    {
        Http::fake([
            'https://shop.test/image' => Http::response($body, 200, ['Content-Type' => $contentType]),
        ]);

        $this->assertNull($this->downloader()->download('https://shop.test/image', 'wishes'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public static function notImageProvider(): array
    {
        return [
            'html with image content type' => ['<html><script>alert(1)</script></html>', 'image/jpeg'],
            'json' => ['{"secret": "value"}', 'application/json'],
            'svg' => ['<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>', 'image/svg+xml'],
        ];
    }

    public function test_does_not_store_too_large_image(): void
    {
        $body = $this->pngImage().str_repeat("\0", ImageDownloader::MAX_SIZE_BYTES);
        Http::fake([
            'https://shop.test/image' => Http::response($body, 200, ['Content-Type' => 'image/png']),
        ]);

        $this->assertNull($this->downloader()->download('https://shop.test/image', 'wishes'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_does_not_store_failed_response(): void
    {
        Http::fake([
            'https://shop.test/image' => Http::response($this->pngImage(), 404, ['Content-Type' => 'image/png']),
        ]);

        $this->assertNull($this->downloader()->download('https://shop.test/image', 'wishes'));
    }

    public function test_follows_redirect_to_public_host(): void
    {
        $this->fakeHostResolver([
            'shop.test' => [self::PUBLIC_IP],
            'cdn.test' => [self::PUBLIC_IP],
        ]);
        Http::fake([
            'https://shop.test/image' => Http::response('', 302, ['Location' => 'https://cdn.test/image.png']),
            'https://cdn.test/image.png' => Http::response($this->pngImage(), 200, ['Content-Type' => 'image/png']),
        ]);

        $path = $this->downloader()->download('https://shop.test/image', 'wishes');

        $this->assertNotNull($path);
        Http::assertSentCount(2);
    }

    public function test_does_not_follow_redirect_to_internal_host(): void
    {
        $this->fakeHostResolver([
            'shop.test' => [self::PUBLIC_IP],
            'internal.test' => ['127.0.0.1'],
        ]);
        Http::fake([
            'https://shop.test/image' => Http::response('', 302, ['Location' => 'http://internal.test/admin']),
            'http://internal.test/*' => Http::response($this->pngImage(), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->assertNull($this->downloader()->download('https://shop.test/image', 'wishes'));
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), 'internal.test'));
    }

    public function test_stops_after_too_many_redirects(): void
    {
        Http::fake([
            'https://shop.test/*' => Http::response('', 302, ['Location' => '/loop']),
        ]);

        $this->assertNull($this->downloader()->download('https://shop.test/image', 'wishes'));
        Http::assertSentCount(4);
    }

    private function downloader(): ImageDownloader
    {
        return resolve(ImageDownloader::class);
    }
}
