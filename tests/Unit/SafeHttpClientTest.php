<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Network\SafeHttpClient;
use App\Services\Network\SafeHttpResponse;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeHttpClientTest extends TestCase
{
    private const LIMIT = 1024;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_returns_body_content_type_and_final_url(): void
    {
        Http::fake([
            'https://shop.test/page' => Http::response('<html></html>', 200, ['Content-Type' => 'text/html; charset=utf-8']),
        ]);

        $response = $this->fetch('https://shop.test/page');

        $this->assertInstanceOf(SafeHttpResponse::class, $response);
        $this->assertSame('<html></html>', $response->body);
        $this->assertSame('text/html; charset=utf-8', $response->contentType);
        $this->assertSame('https://shop.test/page', $response->finalUrl);
    }

    #[DataProvider('internalIpProvider')]
    public function test_does_not_request_internal_hosts(string $ip): void
    {
        $this->fakeHostResolver([$ip]);
        Http::fake();

        $this->assertNull($this->fetch('http://internal.test/latest/meta-data'));
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

        $this->assertNull($this->fetch('https://mixed.test/page'));
        Http::assertNothingSent();
    }

    public function test_does_not_request_unresolvable_host(): void
    {
        $this->fakeHostResolver([]);
        Http::fake();

        $this->assertNull($this->fetch('https://unknown.test/page'));
        Http::assertNothingSent();
    }

    #[DataProvider('unsupportedUrlProvider')]
    public function test_does_not_request_unsupported_urls(string $url): void
    {
        Http::fake();

        $this->assertNull($this->fetch($url));
        Http::assertNothingSent();
    }

    public static function unsupportedUrlProvider(): array
    {
        return [
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://shop.test/page'],
            'gopher scheme' => ['gopher://shop.test:6379/_INFO'],
            'without host' => ['http:///page'],
        ];
    }

    public function test_follows_redirect_and_reports_final_url(): void
    {
        $this->fakeHostResolver([
            'shop.test' => [self::PUBLIC_IP],
            'cdn.test' => [self::PUBLIC_IP],
        ]);
        Http::fake([
            'https://shop.test/page' => Http::response('', 302, ['Location' => 'https://cdn.test/page']),
            'https://cdn.test/page' => Http::response('ok', 200, ['Content-Type' => 'text/html']),
        ]);

        $response = $this->fetch('https://shop.test/page');

        $this->assertSame('https://cdn.test/page', $response?->finalUrl);
        Http::assertSentCount(2);
    }

    public function test_does_not_follow_redirect_to_internal_host(): void
    {
        $this->fakeHostResolver([
            'shop.test' => [self::PUBLIC_IP],
            'internal.test' => ['127.0.0.1'],
        ]);
        Http::fake([
            'https://shop.test/page' => Http::response('', 302, ['Location' => 'http://internal.test/admin']),
            'http://internal.test/*' => Http::response('secret', 200),
        ]);

        $this->assertNull($this->fetch('https://shop.test/page'));
        Http::assertSentCount(1);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'internal.test'));
    }

    public function test_stops_after_too_many_redirects(): void
    {
        Http::fake([
            'https://shop.test/*' => Http::response('', 302, ['Location' => '/loop']),
        ]);

        $this->assertNull($this->fetch('https://shop.test/page'));
        Http::assertSentCount(4);
    }

    public function test_returns_null_for_failed_response(): void
    {
        Http::fake([
            'https://shop.test/page' => Http::response('not found', 404),
        ]);

        $this->assertNull($this->fetch('https://shop.test/page'));
    }

    public function test_returns_null_when_content_length_exceeds_limit(): void
    {
        Http::fake([
            'https://shop.test/page' => Http::response('x', 200, ['Content-Length' => (string) (self::LIMIT * 2)]),
        ]);

        $this->assertNull($this->fetch('https://shop.test/page'));
    }

    public function test_returns_null_when_body_exceeds_limit_without_content_length(): void
    {
        Http::fake([
            'https://shop.test/page' => Http::response(str_repeat('x', self::LIMIT + 1), 200),
        ]);

        $this->assertNull($this->fetch('https://shop.test/page'));
    }

    public function test_size_limit_aborts_transfer_while_downloading(): void
    {
        // Http::fake bypasses curl, so the curl options are checked on a real transfer of a large local file
        $file = tempnam(sys_get_temp_dir(), 'safe-http');
        file_put_contents($file, str_repeat('x', 5 * 1024 * 1024));
        $received = 0;

        $handle = curl_init('file://'.$file);
        curl_setopt_array($handle, SafeHttpClient::sizeLimitCurlOptions(self::LIMIT) + [
            CURLOPT_WRITEFUNCTION => function ($handle, string $chunk) use (&$received): int {
                $received += strlen($chunk);

                return strlen($chunk);
            },
        ]);
        $completed = curl_exec($handle);
        $error = curl_errno($handle);
        unlink($file);

        $this->assertFalse($completed);
        $this->assertSame(CURLE_ABORTED_BY_CALLBACK, $error);
        $this->assertLessThan(5 * 1024 * 1024, $received);
    }

    public function test_sends_given_headers(): void
    {
        Http::fake([
            'https://shop.test/page' => Http::response('ok', 200),
        ]);

        resolve(SafeHttpClient::class)->get('https://shop.test/page', self::LIMIT, ['User-Agent' => 'facebookexternalhit/1.1']);

        Http::assertSent(fn (Request $request): bool => $request->header('User-Agent') === ['facebookexternalhit/1.1']);
    }

    private function fetch(string $url): ?SafeHttpResponse
    {
        return resolve(SafeHttpClient::class)->get($url, self::LIMIT);
    }
}
