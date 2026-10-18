<?php

declare(strict_types=1);

namespace App\Services\Network;

use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Requests user-provided URLs without letting them reach internal hosts (SSRF).
 */
class SafeHttpClient
{
    private const MAX_REDIRECTS = 3;

    private const TIMEOUT_SECONDS = 10;

    private const CONNECT_TIMEOUT_SECONDS = 5;

    /**
     * NAT64 and 6to4 prefixes can embed an internal IPv4 address.
     */
    private const FORBIDDEN_IPV6_PREFIXES = [
        "\x00\x64\xff\x9b",
        "\x20\x02",
    ];

    public function __construct(private readonly HostResolver $hostResolver) {}

    /**
     * @param  array<string, string>  $headers
     */
    public function get(string $url, int $maxBytes, array $headers = []): ?SafeHttpResponse
    {
        $response = $this->request($url, $maxBytes, $headers);

        for ($redirects = 0; $response?->redirect() && $redirects < self::MAX_REDIRECTS; $redirects++) {
            $location = $response->header('Location');
            if ($location === '') {
                return null;
            }

            $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));
            $response = $this->request($url, $maxBytes, $headers);
        }

        if (! $response?->successful()) {
            return null;
        }

        $body = $response->body();
        if (strlen($body) > $maxBytes || (int) $response->header('Content-Length') > $maxBytes) {
            return null;
        }

        return new SafeHttpResponse($body, $response->header('Content-Type'), $url);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function request(string $url, int $maxBytes, array $headers): ?Response
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';
        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return null;
        }

        $ip = $this->resolvePublicIp($host);
        if ($ip === null) {
            return null;
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $pinnedIp = str_contains($ip, ':') ? '['.$ip.']' : $ip;

        try {
            return Http::withoutRedirecting()
                ->withHeaders($headers)
                ->timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->withOptions([
                    // Pin the checked IP, so DNS rebinding can't swap it before the request
                    'curl' => [CURLOPT_RESOLVE => [sprintf('%s:%d:%s', trim($host, '[]'), $port, $pinnedIp)]],
                    'on_headers' => function (ResponseInterface $response) use ($maxBytes): void {
                        if ((int) $response->getHeaderLine('Content-Length') > $maxBytes) {
                            throw new RuntimeException('Response is too large');
                        }
                    },
                ])
                ->get($url);
        } catch (ConnectionException|TransferException) {
            return null;
        }
    }

    private function resolvePublicIp(string $host): ?string
    {
        $ips = $this->hostResolver->resolve($host);
        if ($ips === []) {
            return null;
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                return null;
            }
        }

        return $ips[0];
    }

    private function isPublicIp(string $ip): bool
    {
        // On PHP 8.4 FILTER_FLAG_GLOBAL_RANGE has the value of FILTER_THROW_ON_FAILURE from PHP 8.5 stubs,
        // so PHPStan wrongly assumes that filter_var() can't return false here
        // @phpstan-ignore identical.alwaysFalse
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) === false) {
            return false;
        }

        $packed = (string) inet_pton($ip);
        foreach (self::FORBIDDEN_IPV6_PREFIXES as $prefix) {
            if (strlen($packed) === 16 && str_starts_with($packed, $prefix)) {
                return false;
            }
        }

        return true;
    }
}
