<?php

declare(strict_types=1);

namespace App\Services\WishImport;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use InvalidArgumentException;

/**
 * Extracts product data from a page: JSON-LD first, then meta tags, then the <title> tag.
 */
class ProductPageParser
{
    public const TITLE_MAX_LENGTH = 255;

    public const DESCRIPTION_MAX_LENGTH = 2000;

    private const PRODUCT_TYPES = ['Product', 'ProductGroup'];

    /**
     * @param  string  $html  UTF-8 encoded page
     * @param  string  $pageUrl  relative image URLs are resolved against it
     */
    public function parse(string $html, string $pageUrl): ImportedProduct
    {
        $xpath = $this->loadXPath($html);
        $product = $this->findJsonLdProduct($xpath) ?? [];
        $offer = $this->findOffer($product);

        $title = $this->text($product['name'] ?? null, self::TITLE_MAX_LENGTH)
            ?? $this->text($this->meta($xpath, 'og:title'), self::TITLE_MAX_LENGTH)
            ?? $this->text($xpath->evaluate('string(//title)'), self::TITLE_MAX_LENGTH);

        $description = $this->text($product['description'] ?? null, self::DESCRIPTION_MAX_LENGTH)
            ?? $this->text($this->meta($xpath, 'og:description'), self::DESCRIPTION_MAX_LENGTH);

        $imageUrl = $this->imageUrl($this->firstImage($product['image'] ?? null), $pageUrl)
            ?? $this->imageUrl($this->meta($xpath, 'og:image'), $pageUrl);

        $amount = $this->amount($offer['price'] ?? $offer['lowPrice'] ?? null)
            ?? $this->amount($this->meta($xpath, 'product:price:amount'));

        $currency = $this->currency($offer['priceCurrency'] ?? null)
            ?? $this->currency($this->meta($xpath, 'product:price:currency'));

        return new ImportedProduct($title, $description, $imageUrl, $amount, $currency);
    }

    private function loadXPath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        $useInternalErrors = libxml_use_internal_errors(true);
        // libxml decodes the page by its <meta charset>, which can be stale after the page was converted to UTF-8,
        // so non-ASCII characters are passed as numeric entities
        $dom->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8'), LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        return new DOMXPath($dom);
    }

    /**
     * @return array<mixed>|null
     */
    private function findJsonLdProduct(DOMXPath $xpath): ?array
    {
        foreach ($xpath->query('//script[@type="application/ld+json"]') ?: [] as $script) {
            $data = json_decode($script->textContent, true);
            if (! is_array($data)) {
                continue;
            }

            $nodes = array_is_list($data) ? $data : [$data];
            if (isset($data['@graph']) && is_array($data['@graph'])) {
                $nodes = [...$nodes, ...$data['@graph']];
            }

            foreach ($nodes as $node) {
                if (is_array($node) && array_intersect((array) ($node['@type'] ?? []), self::PRODUCT_TYPES) !== []) {
                    return $node;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<mixed>  $product
     * @return array<mixed>
     */
    private function findOffer(array $product): array
    {
        $offers = $product['offers'] ?? null;
        // A product group keeps prices in its variants
        if ($offers === null && is_array($product['hasVariant'] ?? null)) {
            $variant = $this->first($product['hasVariant']);
            $offers = is_array($variant) ? ($variant['offers'] ?? null) : null;
        }

        $offer = is_array($offers) ? $this->first($offers) : null;

        return is_array($offer) ? $offer : [];
    }

    /**
     * Returns the first element of a list, or the value itself when it is a single object.
     *
     * @param  array<mixed>  $value
     */
    private function first(array $value): mixed
    {
        return array_is_list($value) ? ($value[0] ?? null) : $value;
    }

    private function firstImage(mixed $image): mixed
    {
        if (is_array($image)) {
            $image = $this->first($image);
        }

        return is_array($image) ? ($image['url'] ?? null) : $image;
    }

    private function meta(DOMXPath $xpath, string $name): ?string
    {
        $content = $xpath->evaluate(sprintf('string(//meta[@property="%1$s" or @name="%1$s"]/@content)', $name));

        return is_string($content) && $content !== '' ? $content : null;
    }

    private function text(mixed $value, int $maxLength): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return $text === '' ? null : mb_substr($text, 0, $maxLength);
    }

    private function imageUrl(mixed $value, string $pageUrl): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $url = UriResolver::resolve(new Uri($pageUrl), new Uri(trim($value)));
        } catch (InvalidArgumentException) {
            return null;
        }

        return in_array($url->getScheme(), ['http', 'https'], true) ? (string) $url : null;
    }

    private function amount(mixed $value): ?float
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        $clean = (string) preg_replace('/[^\d.,-]/', '', (string) $value);
        // "1.299,00" and "1,299.00" are both 1299: the last separator is decimal only when 1-2 digits follow it
        if (preg_match('/^(.*)[.,](\d{1,2})$/', $clean, $matches) === 1) {
            $clean = preg_replace('/[.,]/', '', $matches[1]).'.'.$matches[2];
        } else {
            $clean = (string) preg_replace('/[.,]/', '', $clean);
        }

        if (! is_numeric($clean) || (float) $clean < 0) {
            return null;
        }

        return (float) $clean;
    }

    private function currency(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return strtoupper(trim($value));
    }
}
