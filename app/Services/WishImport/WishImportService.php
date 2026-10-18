<?php

declare(strict_types=1);

namespace App\Services\WishImport;

use App\Models\Currency;
use App\Services\Network\SafeHttpClient;

/**
 * Fills a wish from the product page the user links to.
 */
class WishImportService
{
    public const MAX_PAGE_BYTES = 3 * 1024 * 1024;

    // Shops serve product metadata to link preview bots, while browsers may get a bot queue instead
    private const USER_AGENT = 'facebookexternalhit/1.1';

    private const HTML_TYPES = ['text/html', 'application/xhtml+xml'];

    // The charset declaration is expected in the first bytes of the page
    private const CHARSET_SEARCH_BYTES = 2048;

    public function __construct(
        private readonly SafeHttpClient $httpClient,
        private readonly ProductPageParser $parser,
    ) {}

    public function import(string $url): WishImportResult
    {
        $response = $this->httpClient->get($url, self::MAX_PAGE_BYTES, [
            'User-Agent' => self::USER_AGENT,
            'Accept' => 'text/html,application/xhtml+xml',
        ]);

        if ($response === null || ! $this->isHtml($response->contentType)) {
            return WishImportResult::failed("Couldn't load the page");
        }

        $html = $this->toUtf8($response->body, $response->contentType);
        $product = $this->parser->parse($html, $response->finalUrl);
        if ($product->isEmpty()) {
            return WishImportResult::failed('No product data found on this page');
        }

        if ($product->currency !== null && ! Currency::where('short_code', $product->currency)->exists()) {
            $product = $product->withCurrency(null);
        }

        return WishImportResult::success($product);
    }

    private function isHtml(string $contentType): bool
    {
        $mediaType = strtolower(trim(explode(';', $contentType)[0]));

        return in_array($mediaType, self::HTML_TYPES, true);
    }

    private function toUtf8(string $html, string $contentType): string
    {
        $charset = $this->findCharset($contentType) ?? $this->findCharset(substr($html, 0, self::CHARSET_SEARCH_BYTES));
        if ($charset === null || strtoupper($charset) === 'UTF-8') {
            return $html;
        }

        $knownEncodings = array_map(strtoupper(...), mb_list_encodings());
        if (! in_array(strtoupper($charset), $knownEncodings, true)) {
            return $html;
        }

        return mb_convert_encoding($html, 'UTF-8', $charset);
    }

    private function findCharset(string $text): ?string
    {
        return preg_match('/charset=["\']?([\w-]+)/i', $text, $matches) === 1 ? $matches[1] : null;
    }
}
