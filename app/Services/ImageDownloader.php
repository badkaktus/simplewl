<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Network\HostResolver;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Downloads user-provided images without letting the URL reach internal hosts (SSRF).
 */
class ImageDownloader
{
    public const MAX_SIZE_BYTES = 5 * 1024 * 1024;

    private const MAX_REDIRECTS = 3;

    private const TIMEOUT_SECONDS = 10;

    private const CONNECT_TIMEOUT_SECONDS = 5;

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * NAT64 and 6to4 prefixes can embed an internal IPv4 address.
     */
    private const FORBIDDEN_IPV6_PREFIXES = [
        "\x00\x64\xff\x9b",
        "\x20\x02",
    ];

    public function __construct(private readonly HostResolver $hostResolver) {}

    /**
     * @return string|null path of the stored file on the public disk
     */
    public function download(string $url, string $directory): ?string
    {
        $response = $this->get($url);

        for ($redirects = 0; $response?->redirect() && $redirects < self::MAX_REDIRECTS; $redirects++) {
            $location = $response->header('Location');
            if ($location === '') {
                return null;
            }

            $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));
            $response = $this->get($url);
        }

        if (! $response?->successful()) {
            return null;
        }

        $body = $response->body();
        if (strlen($body) > self::MAX_SIZE_BYTES) {
            return null;
        }

        // The content is checked instead of the Content-Type header, so only real images are stored
        $imageInfo = @getimagesizefromstring($body);
        $extension = self::EXTENSIONS[$imageInfo['mime'] ?? ''] ?? null;
        if ($extension === null) {
            return null;
        }

        $filename = $directory.'/'.uniqid('image_', true).'.'.$extension;
        Storage::disk('public')->put($filename, $body);

        return $filename;
    }

    private function get(string $url): ?Response
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
                ->timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->withOptions([
                    // Pin the checked IP, so DNS rebinding can't swap it before the request
                    'curl' => [CURLOPT_RESOLVE => [sprintf('%s:%d:%s', trim($host, '[]'), $port, $pinnedIp)]],
                    'on_headers' => function (ResponseInterface $response): void {
                        if ((int) $response->getHeaderLine('Content-Length') > self::MAX_SIZE_BYTES) {
                            throw new RuntimeException('Image is too large');
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
