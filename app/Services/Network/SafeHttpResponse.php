<?php

declare(strict_types=1);

namespace App\Services\Network;

final readonly class SafeHttpResponse
{
    /**
     * @param  string  $finalUrl  URL after redirects, relative links on the page are resolved against it
     */
    public function __construct(
        public string $body,
        public string $contentType,
        public string $finalUrl,
    ) {}
}
