# Импорт желания по ссылке на товар

Дата: 2026-10-18
Статус: согласован, ожидает плана реализации

## Цель

Пользователь вставляет ссылку на товар (Amazon, Sideshow и любые другие магазины) в форму
создания желания, нажимает кнопку — и форма заполняется данными со страницы: название,
описание, картинка, цена, валюта. Пользователь правит поля и сохраняет как обычно.

### Критерии успеха

- Страницы с JSON-LD `Product` (Sideshow, большинство Shopify/WooCommerce/Magento) —
  заполняются название, описание, картинка, цена и валюта.
- Страницы только с Open Graph (Amazon) — заполняются как минимум название и картинка.
- Если загрузить страницу или найти данные не удалось — пользователь видит понятное
  сообщение, форма не меняется, ошибок 500 нет.
- Загрузка страницы не может достучаться до внутренних адресов (SSRF).

### Вне рамок

- Адаптеры под конкретные сайты (в том числе разбор цены из HTML Amazon).
- Фолбэк на `GptService`.
- Headless-браузер (Puppeteer/Playwright), прокси, обход капчи.
- Импорт в форме редактирования желания.
- Сохранение результата импорта на сервере — импорт только заполняет форму.

## Принятые решения

| Вопрос | Решение | Причина |
|---|---|---|
| Способ извлечения | Свой парсер на `DOMDocument`/`DOMXPath` | Без зависимостей, порядок источников под наш случай, сеть идёт через наш SSRF-safe клиент |
| User-Agent | `facebookexternalhit/1.1` | С браузерным UA Sideshow отдаёт 302 на Queue-it, Amazon отдаёт OG-теги только ботам соцсетей. Осознанный выбор, серая зона по ToS |
| Нет разметки | Поле остаётся пустым | Бесплатно и предсказуемо; GPT-фолбэк — отдельная задача |
| UX | Кнопка «Fill from link», перезаписывает поля, для которых найдено значение | Явное действие, не тратит лимит на каждую вставку |

## Архитектура

```
wish/create (Alpine)
   │  POST /wish/import {url}
   ▼
WishImportController ── ImportWishRequest (валидация)
   │
   ▼
WishImportService
   ├── SafeHttpClient::get(url, maxBytes, headers)   ← общий с ImageDownloader
   └── ProductPageParser::parse(html, pageUrl) → ImportedProduct
```

### `App\Services\Network\SafeHttpClient` (новый, выносится из `ImageDownloader`)

Отвечает только за безопасный HTTP GET к пользовательскому URL.

```php
public function get(string $url, int $maxBytes, array $headers = []): ?SafeHttpResponse
```

- Схема только `http`/`https`, хост обязателен.
- Резолв через `HostResolver`; запрос отклоняется, если **любой** IP не публичный
  (`FILTER_FLAG_GLOBAL_RANGE` + запрет NAT64/6to4 префиксов — логика переносится как есть).
- Проверенный IP пинится через `CURLOPT_RESOLVE` (защита от DNS rebinding).
- Редиректы обрабатываются вручную, не более 3, каждый шаг проходит те же проверки.
- Таймауты: 10 с на запрос, 5 с на соединение (как сейчас в `ImageDownloader`).
- Лимит размера: проверка `Content-Length` в `on_headers` и фактической длины тела.
- Возвращает `null` при любой ошибке.
- `SafeHttpResponse` — DTO: `body`, `contentType`, `finalUrl` (URL после редиректов,
  нужен для разрешения относительных ссылок).

`ImageDownloader` после рефакторинга использует `SafeHttpClient` и отвечает только за
проверку, что тело — картинка, и сохранение файла. Его поведение и публичный API не меняются,
`ImageDownloaderTest` проходит без правок.

### `App\Services\WishImport\ProductPageParser` (новый)

Чистая функция без сети: `parse(string $html, string $pageUrl): ImportedProduct`.

Источники по приоритету; каждое поле берётся из первого источника, где оно есть:

1. **JSON-LD**: все `<script type="application/ld+json">`. Узел `@type` = `Product` или
   `ProductGroup` (строка или массив типов; у `ProductGroup` без `offers` цена берётся из
   `hasVariant[0].offers`) ищется в корне, в массиве верхнего уровня и в `@graph`.
   Битый JSON пропускается.
   - `name` → title, `description` → description
   - `image`: строка | массив (первый элемент) | `ImageObject` (`url`)
   - `offers`: объект | массив (первый элемент); `price`, иначе `lowPrice`
     (`AggregateOffer`); `priceCurrency`
2. **Meta**: `og:title`, `og:description`, `og:image`, `product:price:amount`,
   `product:price:currency` (атрибуты `property` и `name`).
3. **Фолбэк**: `<title>` → title.

### `App\Services\WishImport\ImportedProduct` (новый DTO)

`title`, `description`, `imageUrl`, `currency` — `?string`, `amount` — `?float`.
Метод `isEmpty()`: true, если нет ни `title`, ни `imageUrl`.

### `App\Services\WishImport\WishImportResult` (новый DTO)

Именованные конструкторы `success(ImportedProduct)` и `failed(string $errorMessage)`;
`toArray()` отдаёт формат ответа контроллера.

### Нормализация

- `title`, `description`: `strip_tags`, `html_entity_decode`, схлопывание пробелов, `trim`; пустая строка
  → `null`. `title` обрезается до 255 символов (лимит `ValidationHelper`), `description` —
  до 2000.
- `amount`: число или числовая строка. Строка очищается от символов валюты и пробелов;
  `"1,299.00"` → `1299.00`, `"1.299,00"` → `1299.00` (последний разделитель — десятичный,
  если после него 1–2 цифры). Неразборчивое → `null`. Отрицательные → `null`.
- `currency`: `strtoupper(trim())`; если кода нет в `currencies.short_code` → `null`
  (проверка в `WishImportService` запросом к модели `Currency`, парсер остаётся без БД).
- `imageUrl`: разрешается относительно `finalUrl` (`UriResolver`); принимается только
  `http`/`https`, иначе `null`.

### `App\Services\WishImport\WishImportService` (новый)

```php
public function import(string $url): WishImportResult
```

- `SafeHttpClient::get($url, 3 * 1024 * 1024, ['User-Agent' => 'facebookexternalhit/1.1',
  'Accept' => 'text/html,application/xhtml+xml'])`.
- `null` → результат `failed('Couldn\'t load the page')`.
- `Content-Type` не `text/html`/`application/xhtml+xml` → тот же `failed`.
- Кодировка: если charset из `Content-Type` или `<meta charset>`/`http-equiv` не UTF-8 —
  `mb_convert_encoding` в UTF-8.
- Парсинг → фильтр валюты → если `isEmpty()` → `failed('No product data found on this page')`
  (сюда же попадают страницы очередей и капчи).
- Иначе `success(ImportedProduct)`.

### HTTP

`POST /wish/import` → `WishImportController` (invokable), внутри группы `auth`,
middleware `throttle:wish-import`.

- `ImportWishRequest`: `url` — `required`, `string`, `max:2048`, `url:http,https`.
  Ошибки валидации возвращаются в том же формате, что у `GenerateGptDescriptionRequest`.
- Лимитер `wish-import` в `RouteServiceProvider`: 10 в минуту на пользователя
  (константа `WISH_IMPORT_PER_MINUTE`).
- Ответ 200:
  - `{"isSuccess": true, "data": {"title", "description", "imageUrl", "amount", "currency"}}`
  - `{"isSuccess": false, "errorMessage": "..."}`

### Фронтенд (`resources/views/wish/create.blade.php`)

- Рядом с полем URL — кнопка «Fill from link» со спиннером, неактивна при пустом URL и во
  время загрузки.
- Поле URL получает `x-model="url"`, `image_url` — `x-model="imageUrl"`, `amount` —
  `x-model="amount"` в корневом `x-data`.
- По успеху перезаписываются `title`, `descText`, `imageUrl`, `amount` для значений,
  отличных от `null`.
- Валюта: `window.dispatchEvent(new CustomEvent('wish-imported', {detail: {currency}}))`;
  `currency-select` слушает `@wish-imported.window` и ставит `selectedOption`, если код есть
  в `options`.
- Ошибки показываются блоком под полем URL (по образцу `errorGptGenerate`): `errorMessage`
  из ответа, 429 → «Too many requests. Please try again later.», 400 → сообщения валидации,
  прочее → «Something went wrong. Please try again later.».
- JS-логика — по образцу `getGeneratedDescription` в
  `components/textarea-with-generate-description.blade.php`.

## Тестирование

- `tests/Unit/ProductPageParserTest` — фикстуры в `tests/Fixtures/product-pages/`:
  - `sideshow.html` — урезанная реальная страница (JSON-LD в `@graph`, `price: 456.0`)
  - `amazon.html` — урезанная реальная страница для UA бота (только OG, без цены)
  - синтетические кейсы инлайном: `product:price:*` мета, `AggregateOffer`, `offers`
    массивом, `image` как `ImageObject`/массив, `@type` массивом, только `<title>`, битый
    JSON-LD, относительный `og:image`, `javascript:` в картинке, локализованные форматы цены,
    HTML-сущности.
- `tests/Unit/SafeHttpClientTest` — SSRF-кейсы (приватные/loopback/link-local IP, NAT64,
  неразрешимый хост, неподдерживаемые схемы, редирект во внутреннюю сеть, лимит редиректов,
  лимит размера по заголовку и по телу).
- `tests/Unit/WishImportServiceTest` — `Http::fake` + фейковый `HostResolver`: успех,
  сеть недоступна, не-HTML, пустой результат, неизвестная валюта, перекодировка из
  windows-1251.
- `tests/Feature/Http/Controllers/WishImportControllerTest` — гость → редирект на логин,
  невалидный URL → ошибка валидации, успех, неудача, 429 после лимита.
- `ImageDownloaderTest` без изменений остаётся зелёным.
- Перед коммитом: `vendor/bin/pint`, `vendor/bin/phpstan analyse`, `vendor/bin/phpunit`.

## Риски

- Сайты меняют защиту от ботов: Sideshow или Amazon могут перестать отдавать данные боту
  соцсети. Деградация мягкая — сообщение об ошибке, ручное заполнение.
- С IP прод-сервера (дата-центр) Amazon чаще отдаёт капчу, чем с домашнего — ловится как
  «No product data found».
- Цена зависит от региона сервера; валюта берётся из разметки, поэтому сумма и валюта
  согласованы, но могут отличаться от того, что видит пользователь.
