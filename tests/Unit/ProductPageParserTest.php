<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\WishImport\ImportedProduct;
use App\Services\WishImport\ProductPageParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductPageParserTest extends TestCase
{
    private const PAGE_URL = 'https://shop.test/p/1';

    public function test_parses_sideshow_json_ld_graph(): void
    {
        $product = $this->parser()->parse(
            $this->fixture('sideshow.html'),
            'https://www.sideshow.com/collectibles/marvel-iron-man-mark-lxxxv-battle-damaged-version-hot-toys-904923',
        );

        $this->assertSame('Iron Man Mark LXXXV (Battle Damaged Version) Special Edition Sixth Scale Figure', $product->title);
        $this->assertSame(
            'https://www.sideshow.com/storage/product-images/9049231/iron-man-mark-lxxxv-battle-damaged-version-special-edition_marvel_feature.jpg',
            $product->imageUrl,
        );
        $this->assertSame(456.0, $product->amount);
        $this->assertSame('USD', $product->currency);
        $this->assertStringStartsWith('The Iron Man Mark LXXXV', (string) $product->description);
    }

    public function test_parses_amazon_open_graph(): void
    {
        $product = $this->parser()->parse($this->fixture('amazon.html'), 'https://www.amazon.com/dp/B0HGFTXXXM');

        $this->assertStringStartsWith('HP 2026 Everyday Laptop', (string) $product->title);
        $this->assertStringContainsString('Lightweight & Portable', (string) $product->title);
        $this->assertStringStartsWith('https://m.media-amazon.com/images/I/81zV-uXEduL', (string) $product->imageUrl);
        $this->assertNull($product->amount);
        $this->assertNull($product->currency);
    }

    public function test_reads_product_price_meta(): void
    {
        $product = $this->parse(
            '<meta property="og:title" content="Mug">'
            .'<meta property="product:price:amount" content="19.99">'
            .'<meta property="product:price:currency" content="usd">'
        );

        $this->assertSame(19.99, $product->amount);
        $this->assertSame('USD', $product->currency);
    }

    public function test_reads_aggregate_offer_low_price(): void
    {
        $product = $this->parse($this->jsonLd([
            '@type' => 'Product',
            'name' => 'Mug',
            'offers' => ['@type' => 'AggregateOffer', 'lowPrice' => '10.50', 'priceCurrency' => 'EUR'],
        ]));

        $this->assertSame(10.5, $product->amount);
        $this->assertSame('EUR', $product->currency);
    }

    public function test_reads_first_offer_from_array(): void
    {
        $product = $this->parse($this->jsonLd([
            '@type' => 'Product',
            'name' => 'Mug',
            'offers' => [
                ['@type' => 'Offer', 'price' => '5', 'priceCurrency' => 'GBP'],
                ['@type' => 'Offer', 'price' => '7', 'priceCurrency' => 'USD'],
            ],
        ]));

        $this->assertSame(5.0, $product->amount);
        $this->assertSame('GBP', $product->currency);
    }

    public function test_reads_type_given_as_array(): void
    {
        $product = $this->parse($this->jsonLd([
            ['@type' => 'BreadcrumbList'],
            ['@type' => ['Product', 'Thing'], 'name' => 'Mug'],
        ]));

        $this->assertSame('Mug', $product->title);
    }

    #[DataProvider('imageProvider')]
    public function test_reads_image_object_and_image_array(mixed $image): void
    {
        $product = $this->parse($this->jsonLd(['@type' => 'Product', 'name' => 'Mug', 'image' => $image]));

        $this->assertSame('https://a.test/1.jpg', $product->imageUrl);
    }

    public static function imageProvider(): array
    {
        return [
            'image object' => [['@type' => 'ImageObject', 'url' => 'https://a.test/1.jpg']],
            'image array' => [['https://a.test/1.jpg', 'https://a.test/2.jpg']],
        ];
    }

    public function test_reads_product_group_variant_offer(): void
    {
        $product = $this->parse($this->jsonLd([
            '@type' => 'ProductGroup',
            'name' => 'Tee',
            'hasVariant' => [
                ['@type' => 'Product', 'offers' => ['price' => '25', 'priceCurrency' => 'USD']],
            ],
        ]));

        $this->assertSame('Tee', $product->title);
        $this->assertSame(25.0, $product->amount);
        $this->assertSame('USD', $product->currency);
    }

    public function test_json_ld_wins_over_meta_per_field(): void
    {
        $product = $this->parse(
            $this->jsonLd(['@type' => 'Product', 'name' => 'From JSON-LD'])
            .'<meta property="og:title" content="From meta">'
            .'<meta property="og:image" content="https://a.test/meta.jpg">'
        );

        $this->assertSame('From JSON-LD', $product->title);
        $this->assertSame('https://a.test/meta.jpg', $product->imageUrl);
    }

    public function test_skips_invalid_json_ld(): void
    {
        $product = $this->parse(
            '<script type="application/ld+json">{"@type": "Product", "name": </script>'
            .'<meta property="og:title" content="From meta">'
        );

        $this->assertSame('From meta', $product->title);
    }

    public function test_falls_back_to_title_tag(): void
    {
        $product = $this->parse('<title> Shop &amp; Co </title>');

        $this->assertSame('Shop & Co', $product->title);
    }

    public function test_normalizes_text(): void
    {
        $product = $this->parse($this->jsonLd([
            '@type' => 'Product',
            'name' => str_repeat('a', 300),
            'description' => "<p>Line&nbsp;one</p>\n\n<br>  two",
        ]));

        $this->assertSame(255, mb_strlen((string) $product->title));
        $this->assertSame('Line one two', $product->description);
    }

    #[DataProvider('htmlDescriptionProvider')]
    public function test_strips_html_from_description(string $description, string $expected): void
    {
        $product = $this->parse($this->jsonLd(['@type' => 'Product', 'name' => 'Mug', 'description' => $description]));

        $this->assertSame($expected, $product->description);
    }

    public static function htmlDescriptionProvider(): array
    {
        return [
            'adjacent blocks' => ['<p>one</p><p>two</p>line<br>three', 'one two line three'],
            'entity-encoded tags' => ['&lt;p&gt;Hello&lt;/p&gt;&lt;br&gt;World', 'Hello World'],
        ];
    }

    public function test_truncates_long_description(): void
    {
        $product = $this->parse($this->jsonLd(['@type' => 'Product', 'name' => 'Mug', 'description' => str_repeat('б', 2500)]));

        $this->assertSame(2000, mb_strlen((string) $product->description));
    }

    #[DataProvider('priceProvider')]
    public function test_parses_price_formats(mixed $price, ?float $expected): void
    {
        $product = $this->parse($this->jsonLd(['@type' => 'Product', 'name' => 'Mug', 'offers' => ['price' => $price]]));

        $this->assertSame($expected, $product->amount);
    }

    public static function priceProvider(): array
    {
        return [
            'thousands comma' => ['1,299.00', 1299.0],
            'thousands dot' => ['1.299,00', 1299.0],
            'currency symbol' => ['$ 45', 45.0],
            'decimal comma' => ['12,5', 12.5],
            'thousands without decimals' => ['1,299', 1299.0],
            'not a number' => ['abc', null],
            'negative' => ['-5', null],
            'integer' => [456, 456.0],
            'schema.org decimal with trailing zeros' => ['19.9900', 19.99],
            'three decimals' => ['12.990', 12.99],
            'float' => [1.5, 1.5],
        ];
    }

    #[DataProvider('imageUrlProvider')]
    public function test_resolves_image_urls(string $raw, ?string $expected): void
    {
        $product = $this->parse('<meta property="og:title" content="Mug"><meta property="og:image" content="'.$raw.'">');

        $this->assertSame($expected, $product->imageUrl);
    }

    public static function imageUrlProvider(): array
    {
        return [
            'absolute path' => ['/img/a.jpg', 'https://shop.test/img/a.jpg'],
            'protocol-relative' => ['//cdn.shop.test/a.jpg', 'https://cdn.shop.test/a.jpg'],
            'javascript' => ['javascript:alert(1)', null],
            'data uri' => ['data:image/png;base64,AA', null],
        ];
    }

    public function test_returns_empty_product_for_non_product_page(): void
    {
        $product = $this->parser()->parse('<html><body>Queue</body></html>', self::PAGE_URL);

        $this->assertTrue($product->isEmpty());
    }

    private function parse(string $head): ImportedProduct
    {
        return $this->parser()->parse('<!DOCTYPE html><html><head><meta charset="utf-8">'.$head.'</head><body></body></html>', self::PAGE_URL);
    }

    private function jsonLd(array $data): string
    {
        $document = array_is_list($data) ? $data : ['@context' => 'https://schema.org'] + $data;

        return '<script type="application/ld+json">'.json_encode($document).'</script>';
    }

    private function parser(): ProductPageParser
    {
        return new ProductPageParser;
    }

    private function fixture(string $name): string
    {
        return (string) file_get_contents(__DIR__.'/../Fixtures/product-pages/'.$name);
    }
}
