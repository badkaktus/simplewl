# Wish Import From URL Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A "Fill from link" button on the wish create form that fetches a product page and fills title, description, image URL, amount and currency from its markup.

**Architecture:** The SSRF-safe HTTP part of `ImageDownloader` moves into `SafeHttpClient`, shared by the downloader and a new `WishImportService`. A network-free `ProductPageParser` reads JSON-LD → meta tags → `<title>`. An invokable controller exposes `POST /wish/import`; Alpine on the create form calls it.

**Tech Stack:** Laravel 12, PHP `DOMDocument`/`DOMXPath`, Guzzle PSR-7 `UriResolver`, Alpine.js + axios, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-10-18-wish-import-from-url-design.md`

## Global Constraints

- User-Agent for page fetch: `facebookexternalhit/1.1`; `Accept: text/html,application/xhtml+xml`.
- Page size limit: `3 * 1024 * 1024` bytes. Timeouts: 10 s request, 5 s connect. Max 3 redirects.
- Title max 255 chars, description max 2000 chars.
- Route `POST /wish/import`, name `wish.import`, in the `auth` group, middleware `throttle:wish-import`, limit 10/min per user (`RouteServiceProvider::WISH_IMPORT_PER_MINUTE = 10`).
- Response 200: `{"isSuccess": true, "data": {"title","description","imageUrl","amount","currency"}}` or `{"isSuccess": false, "errorMessage": "..."}`.
- Error messages exactly: `Couldn't load the page`, `No product data found on this page`.
- Validation errors: HTTP 400 with `$validator->errors()` JSON (same as `GenerateGptDescriptionRequest`).
- New PHP files start with `declare(strict_types=1);`. Code, comments, UI copy in English.
- Before every commit: `vendor/bin/pint`, `vendor/bin/phpstan analyse`, `vendor/bin/phpunit` all green.

## Review Focus

1. Shopify pages describe the item as `ProductGroup` with prices only in `hasVariant[0].offers` — expect title and price to be imported (test in Task 2).
2. JSON-LD `description` with HTML tags (`<p>`, `<br>`) — expect plain text without tags (test in Task 2).
3. Protocol-relative `og:image` (`//cdn.shop.test/a.jpg`) — expect `https://cdn.shop.test/a.jpg` for an https page (test in Task 2).
4. Amazon share URL ~1000 chars with tracking query — expect it to pass validation, not 400 (test in Task 4).
5. Chunked HTML response over the size limit without `Content-Length` — expect `Couldn't load the page`, not a memory spike or partial parse (test in Task 1).

---

### Task 1: Extract `SafeHttpClient` from `ImageDownloader`

**Files:**
- Create: `app/Services/Network/SafeHttpClient.php`
- Create: `app/Services/Network/SafeHttpResponse.php`
- Modify: `app/Services/ImageDownloader.php` (remove `get`, `resolvePublicIp`, `isPublicIp`, redirect loop, constants other than `MAX_SIZE_BYTES`/`EXTENSIONS`)
- Test: `tests/Unit/SafeHttpClientTest.php`; `tests/Unit/ImageDownloaderTest.php` stays unchanged

**Interfaces:**
- Produces:
  - `SafeHttpClient::get(string $url, int $maxBytes, array<string,string> $headers = []): ?SafeHttpResponse` — `null` on unsupported scheme, missing host, non-public IP (any of resolved IPs), connection error, >3 redirects, empty `Location`, non-2xx final status, body over `$maxBytes` (by `Content-Length` header or actual length).
  - `final readonly class SafeHttpResponse { __construct(public string $body, public string $contentType, public string $finalUrl) }` — `contentType` is the raw `Content-Type` header (may be `''`).

- [ ] **Step 1: Write the failing test**

`tests/Unit/SafeHttpClientTest.php` (extends `Tests\TestCase`, `Http::preventStrayRequests()` in `setUp`). Port these cases from `ImageDownloaderTest`, asserting on `resolve(SafeHttpClient::class)->get($url, 1024)`:
- `test_returns_body_content_type_and_final_url` — `https://shop.test/page` → 200 `'<html></html>'`, `text/html; charset=utf-8`; assert the three properties, `finalUrl === 'https://shop.test/page'`.
- `test_does_not_request_internal_hosts` with the same `internalIpProvider` data set → `null`, `Http::assertNothingSent()`.
- `test_does_not_request_host_with_any_internal_ip`, `test_does_not_request_unresolvable_host`, `test_does_not_request_unsupported_urls` (same provider).
- `test_follows_redirect_and_reports_final_url` — `shop.test` 302 → `https://cdn.test/page`; `finalUrl === 'https://cdn.test/page'`, `Http::assertSentCount(2)`.
- `test_does_not_follow_redirect_to_internal_host`, `test_stops_after_too_many_redirects` (`assertSentCount(4)`).
- `test_returns_null_for_failed_response` — 404.
- `test_returns_null_when_content_length_exceeds_limit` — header `Content-Length: 2048`, body `'x'`, limit 1024.
- `test_returns_null_when_body_exceeds_limit_without_content_length` — body `str_repeat('x', 1025)`, no `Content-Length` header, limit 1024 (Review Focus 5).
- `test_sends_given_headers` — `Http::assertSent(fn ($r) => $r->header('User-Agent') === ['facebookexternalhit/1.1'])`.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter SafeHttpClientTest`
Expected: FAIL, `Class "App\Services\Network\SafeHttpClient" not found`.

- [ ] **Step 3: Implement `SafeHttpClient` and `SafeHttpResponse`**

Move `get`/`resolvePublicIp`/`isPublicIp`, `FORBIDDEN_IPV6_PREFIXES`, timeout and redirect constants from `ImageDownloader` verbatim, keeping their comments (including the PHPStan ignore). The `on_headers` check and the post-download `strlen` check use `$maxBytes`; `withHeaders($headers)` is added to the request. The redirect loop moves into the public `get`.

- [ ] **Step 4: Make `ImageDownloader` use it**

Constructor becomes `__construct(private readonly SafeHttpClient $httpClient)`. `download()` calls `$this->httpClient->get($url, self::MAX_SIZE_BYTES)`, then keeps only the `getimagesizefromstring` check and storage.

- [ ] **Step 5: Run tests**

Run: `vendor/bin/phpunit --filter 'SafeHttpClientTest|ImageDownloaderTest|WishServiceTest'`
Expected: PASS, `ImageDownloaderTest.php` has no diff.

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint && vendor/bin/phpstan analyse
git add app/Services/Network app/Services/ImageDownloader.php tests/Unit/SafeHttpClientTest.php
git commit -m "refactor: extract SSRF-safe HTTP client from image downloader"
```

### Task 2: `ProductPageParser`

**Files:**
- Create: `app/Services/WishImport/ImportedProduct.php`
- Create: `app/Services/WishImport/ProductPageParser.php`
- Create: `tests/Fixtures/product-pages/sideshow.html`, `tests/Fixtures/product-pages/amazon.html`
- Test: `tests/Unit/ProductPageParserTest.php`

**Interfaces:**
- Produces:
  - `final readonly class ImportedProduct { __construct(public ?string $title, public ?string $description, public ?string $imageUrl, public ?float $amount, public ?string $currency) }`, `isEmpty(): bool` (no `title` and no `imageUrl`), `withCurrency(?string $currency): self`, `toArray(): array{title: ?string, description: ?string, imageUrl: ?string, amount: ?float, currency: ?string}`.
  - `ProductPageParser::parse(string $html, string $pageUrl): ImportedProduct` — expects UTF-8 HTML; never throws.

- [ ] **Step 1: Save fixtures**

Fetch both example URLs from the spec with `curl -A 'facebookexternalhit/1.1'` and trim each to `<head>` (meta tags, `<title>`, all `application/ld+json` scripts) plus `<body></body>`. Keep `sideshow.html` and `amazon.html` under 30 KB each.

- [ ] **Step 2: Write the failing tests**

Fixture cases:
- `test_parses_sideshow_json_ld_graph` — title `Iron Man Mark LXXXV (Battle Damaged Version) Special Edition Sixth Scale Figure`, imageUrl `https://www.sideshow.com/storage/product-images/9049231/iron-man-mark-lxxxv-battle-damaged-version-special-edition_marvel_feature.jpg`, amount `456.0`, currency equal to the fixture's `priceCurrency` uppercased, description starts with `The Iron Man Mark LXXXV`.
- `test_parses_amazon_open_graph` — title starts with `HP 2026 Everyday Laptop`, imageUrl starts with `https://m.media-amazon.com/images/I/81zV-uXEduL`, amount `null`, currency `null`.

Inline HTML cases (a small `html(string $head)` helper wraps markup in a document):
- `test_reads_product_price_meta` — `product:price:amount` `19.99` + `product:price:currency` `usd` → `19.99`, `USD`.
- `test_reads_aggregate_offer_low_price` — `offers: {"@type":"AggregateOffer","lowPrice":"10.50","priceCurrency":"EUR"}` → `10.5`, `EUR`.
- `test_reads_first_offer_from_array` and `test_reads_type_given_as_array` (`"@type": ["Product","Thing"]`).
- `test_reads_image_object_and_image_array` (data provider: `{"@type":"ImageObject","url":"https://a.test/1.jpg"}`, `["https://a.test/1.jpg","https://a.test/2.jpg"]` → `https://a.test/1.jpg`).
- `test_reads_product_group_variant_offer` — `{"@type":"ProductGroup","name":"Tee","hasVariant":[{"@type":"Product","offers":{"price":"25","priceCurrency":"USD"}}]}` → title `Tee`, `25.0`, `USD` (Review Focus 1).
- `test_json_ld_wins_over_meta_per_field` — JSON-LD has name only, meta has `og:title` + `og:image` → title from JSON-LD, image from meta.
- `test_skips_invalid_json_ld` — one broken script, then valid meta → meta values.
- `test_falls_back_to_title_tag` — only `<title> Shop &amp; Co </title>` → `Shop & Co`.
- `test_normalizes_text` — description `"<p>Line&nbsp;one</p>\n\n<br>  two"` → `Line one two`; title of 300 chars → 255; description of 2500 chars → 2000 (Review Focus 2).
- `test_parses_price_formats` provider: `"1,299.00"`→`1299.0`, `"1.299,00"`→`1299.0`, `"$ 45"`→`45.0`, `"12,5"`→`12.5`, `"1,299"`→`1299.0`, `"abc"`→`null`, `"-5"`→`null`, `456` (int) → `456.0`.
- `test_resolves_image_urls` provider against page `https://shop.test/p/1`: `/img/a.jpg`→`https://shop.test/img/a.jpg`, `//cdn.shop.test/a.jpg`→`https://cdn.shop.test/a.jpg` (Review Focus 3), `javascript:alert(1)`→`null`, `data:image/png;base64,AA`→`null`.
- `test_returns_empty_product_for_non_product_page` — `<html><body>Queue</body></html>` → `isEmpty() === true`.

- [ ] **Step 3: Run tests to verify they fail**

Run: `vendor/bin/phpunit --filter ProductPageParserTest`
Expected: FAIL, class not found.

- [ ] **Step 4: Implement `ImportedProduct` and `ProductPageParser::parse`**

Load with `libxml_use_internal_errors(true)` and `$dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET)`. Product node search: walk root, top-level list and `@graph`; accept `@type` `Product` or `ProductGroup` (string or array); for `ProductGroup` without `offers`, use `hasVariant[0].offers`. Meta lookup by `@property` or `@name`. Text normalization: `strip_tags` → `html_entity_decode(ENT_QUOTES | ENT_HTML5, 'UTF-8')` → replace `\s+` (with `u` flag, includes NBSP) by one space → `trim` → `mb_substr` to the limit; `''` → `null`. Image: `UriResolver::resolve(new Uri($pageUrl), new Uri($raw))`, keep only `http`/`https`.

Price parsing (not determined by the tests alone):

```php
// "1.299,00" and "1,299.00" are both 1299: the last separator is decimal only when 1-2 digits follow it
$clean = preg_replace('/[^\d.,-]/', '', $raw);
if (preg_match('/^(.*)[.,](\d{1,2})$/', $clean, $m)) {
    $clean = preg_replace('/[.,]/', '', $m[1]).'.'.$m[2];
} else {
    $clean = preg_replace('/[.,]/', '', $clean);
}
```

Result must be `is_numeric` and `>= 0`, else `null`.

- [ ] **Step 5: Run tests**

Run: `vendor/bin/phpunit --filter ProductPageParserTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint && vendor/bin/phpstan analyse
git add app/Services/WishImport tests/Unit/ProductPageParserTest.php tests/Fixtures
git commit -m "feat: parse product data from JSON-LD and meta tags"
```

### Task 3: `WishImportService`

**Files:**
- Create: `app/Services/WishImport/WishImportResult.php`
- Create: `app/Services/WishImport/WishImportService.php`
- Test: `tests/Unit/WishImportServiceTest.php`

**Interfaces:**
- Consumes: `SafeHttpClient::get` (Task 1), `ProductPageParser::parse`, `ImportedProduct::withCurrency/isEmpty/toArray` (Task 2).
- Produces:
  - `final readonly class WishImportResult` with `static success(ImportedProduct $product): self`, `static failed(string $errorMessage): self`, `toArray(): array` → `['isSuccess' => true, 'data' => $product->toArray()]` or `['isSuccess' => false, 'errorMessage' => $message]`.
  - `WishImportService::import(string $url): WishImportResult`; `public const MAX_PAGE_BYTES = 3 * 1024 * 1024`.

- [ ] **Step 1: Write the failing tests**

`Http::preventStrayRequests()`; `Currency::factory()->create(['short_code' => 'USD'])` where needed.
- `test_imports_product` — JSON-LD page with `USD` price → `toArray()` has `isSuccess: true`, `data.amount: 456.0`, `data.currency: 'USD'`; `Http::assertSent` with UA `facebookexternalhit/1.1`.
- `test_drops_unknown_currency` — `priceCurrency: "XYZ"` → `data.currency === null`, amount kept.
- `test_fails_when_page_cannot_be_loaded` — 500 response → `['isSuccess' => false, 'errorMessage' => "Couldn't load the page"]`.
- `test_fails_for_non_html_content` — `application/json` → same message.
- `test_fails_when_no_product_data` — queue-like page → `No product data found on this page`.
- `test_converts_page_encoding` — body `mb_convert_encoding('<html><head><meta charset="windows-1251"><title>Кружка</title></head></html>', 'Windows-1251', 'UTF-8')`, header `text/html` → `data.title === 'Кружка'`; same with charset only in `Content-Type: text/html; charset=windows-1251`.
- `test_resolves_relative_image_against_final_url` — 302 to `https://cdn.test/p`, page with `og:image` `/a.jpg` → `https://cdn.test/a.jpg`.

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit --filter WishImportServiceTest`
Expected: FAIL, class not found.

- [ ] **Step 3: Implement `WishImportResult` and `WishImportService::import`**

Accept `text/html` and `application/xhtml+xml` by the media type part of `contentType`. Charset: from `Content-Type`, else first `<meta charset>` / `http-equiv` content within the first 2048 bytes; convert with `mb_convert_encoding($body, 'UTF-8', $charset)` when it is not UTF-8 and `in_array(strtoupper($charset), array_map('strtoupper', mb_list_encodings()))` (unknown charset → parse as is). Parse with `$response->finalUrl`. Keep currency only if `Currency::where('short_code', $code)->exists()`.

- [ ] **Step 4: Run tests**

Run: `vendor/bin/phpunit --filter WishImportServiceTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint && vendor/bin/phpstan analyse
git add app/Services/WishImport tests/Unit/WishImportServiceTest.php
git commit -m "feat: import wish data from product page URL"
```

### Task 4: Endpoint `POST /wish/import`

**Files:**
- Create: `app/Http/Requests/ImportWishRequest.php`
- Create: `app/Http/Controllers/WishImportController.php`
- Modify: `routes/web.php` (inside the `auth` group, next to `generate-description`)
- Modify: `app/Providers/RouteServiceProvider.php` (constant + limiter)
- Test: `tests/Feature/Http/Controllers/WishImportControllerTest.php`

**Interfaces:**
- Consumes: `WishImportService::import`, `WishImportResult::toArray` (Task 3).
- Produces: route name `wish.import`; `ImportWishRequest::getUrl(): string`.

- [ ] **Step 1: Write the failing tests**

- `test_guest_is_redirected_to_login` — `post('/wish/import', ['url' => 'https://shop.test/p'])` → redirect to `/login`.
- `test_returns_imported_product` — `Http::fake` JSON-LD page → 200, `assertJsonPath('isSuccess', true)`, `assertJsonPath('data.title', ...)`.
- `test_returns_failed_result` — `Http::fake` 500 → 200, `assertExactJson(['isSuccess' => false, 'errorMessage' => "Couldn't load the page"])`.
- `test_validates_url` provider: missing, `'not a url'`, `'ftp://shop.test/p'`, `'https://shop.test/'.str_repeat('a', 2048)` → 400 with `url` key.
- `test_accepts_long_amazon_url` — the Amazon URL from the spec conversation (~1000 chars with `ref=`, `dib=`, `keywords=` params) → not 400 (Review Focus 4).
- `test_is_rate_limited` — 10 requests 200, 11th → 429 (`RouteServiceProvider::WISH_IMPORT_PER_MINUTE`).

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit --filter WishImportControllerTest`
Expected: FAIL, 404 / route not defined.

- [ ] **Step 3: Implement**

`ImportWishRequest`: rule `'url' => ['required', 'string', 'max:2048', 'url:http,https']`, `authorize(): true`, `failedValidation` copied from `GenerateGptDescriptionRequest`. Controller mirrors `WishDescriptionGenerateController`: `__invoke(ImportWishRequest $request): JsonResponse` → `response()->json($this->wishImportService->import($request->getUrl())->toArray())`. Limiter: `RateLimiter::for('wish-import', fn (Request $request): Limit => Limit::perMinute(self::WISH_IMPORT_PER_MINUTE)->by($request->user()?->id))`. Route: `Route::post('/wish/import', WishImportController::class)->middleware('throttle:wish-import')->name('wish.import');` placed before the `scopeBindings` group.

- [ ] **Step 4: Run tests**

Run: `vendor/bin/phpunit`
Expected: PASS (whole suite).

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint && vendor/bin/phpstan analyse
git add app/Http routes/web.php app/Providers/RouteServiceProvider.php tests/Feature/Http/Controllers/WishImportControllerTest.php
git commit -m "feat: add wish import endpoint"
```

### Task 5: "Fill from link" in the create form

**Files:**
- Modify: `resources/views/wish/create.blade.php`
- Modify: `resources/views/components/currency-select.blade.php`
- Test: `tests/Feature/Http/Controllers/WishControllerTest.php` (one render assertion)

**Interfaces:**
- Consumes: route `wish.import` and its JSON (Task 4).
- Produces: window event `wish-imported` with `detail: {currency: ?string}`.

- [ ] **Step 1: Write the failing test**

`test_create_page_has_import_button` — authenticated `get(route('wish.create'))` → `assertSee('Fill from link')` and `assertSee(route('wish.import'), false)`.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter test_create_page_has_import_button`
Expected: FAIL.

- [ ] **Step 3: Implement**

- Root `x-data` gains `url: ""`, `imageUrl: ""`, `amount: ""`, `importing: false`, `errorImport: ""`; inputs `url`, `image_url`, `amount` get matching `x-model`.
- Button `Fill from link` right of the URL input, `type="button"`, `:disabled="importing || !url.trim()"`, spinner while `importing` (reuse the SVG from `textarea-with-generate-description`).
- `importFromUrl()` follows `getGeneratedDescription`: on `isSuccess` assign `title`, `descText`, `imageUrl`, `amount` only for non-`null` values, then `window.dispatchEvent(new CustomEvent('wish-imported', {detail: {currency: data.currency}}))`; error block under the URL input with the same 429 / 400 / fallback messages.
- `currency-select`: root element gets `@wish-imported.window="if ($event.detail.currency && options.some(o => o.short_code === $event.detail.currency)) selectedOption = $event.detail.currency"`.

- [ ] **Step 4: Run tests and check manually**

Run: `vendor/bin/phpunit`
Expected: PASS.
Manual: `npm run dev` + `php artisan serve`, open `/wish/create`, paste the Sideshow URL → all five fields filled, currency selected; paste the Amazon URL → title and image filled; paste `https://example.com` → "No product data found on this page"; submit the form → wish saved with downloaded image.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint && vendor/bin/phpstan analyse
git add resources/views tests/Feature/Http/Controllers/WishControllerTest.php
git commit -m "feat: fill wish form from product link"
```
