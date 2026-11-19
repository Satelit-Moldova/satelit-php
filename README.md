# satelit-php

PHP client for the [Satelit data API](https://developer.satelit.md/en/docs/): Moldova's official exchange rates, fuel prices, tariffs, law, news, shop prices, tours, flights, companies, court hearings, Romanian citizenship, movies and more, through one key. No dependencies, PHP 7.2 to 8.4, with Composer or a single `require` on hosting without it.

```php
require 'satelit-php/src/autoload.php';   // or vendor/autoload.php

$satelit = new Satelit\Client(getenv('SATELIT_API_KEY'), [
    'cache' => new Satelit\Cache\FileCache(__DIR__ . '/cache'),
]);
$rates = $satelit->rates()->list();
foreach ($rates as $r) {
    echo $r['code'], ' ', $r['value'], "\n";
}
echo $rates->meta->attribution;   // show it wherever the data is shown
```

```console
$ php examples/quickstart.php
EUR: 20.1411 lei (2026-11-19)
Sursa: Banca Națională a Moldovei, bnm.md, CC BY 4.0
Cheapest A95: TLX, 27.90 lei/l
Lapte Casuta Mea 2.5% 1 l: from 15.99 lei in 4 shops
Data: Satelit (satelit.md). Credits left: 293645
```

(Real output of a live run on PHP 7.2, 19 November 2026.)

## Contents

- [Requirements](#requirements)
- [Install](#install)
- [Configure](#configure)
- [Usage](#usage)
- [Methods](#methods)
- [Errors](#errors)
- [Credits and caching](#credits-and-caching)
- [Pagination, feed and export](#pagination-feed-and-export)
- [Examples](#examples)
- [Tests](#tests)
- [Tested with](#tested-with)
- [Troubleshooting](#troubleshooting)
- [Pe scurt (română)](#pe-scurt-română)
- [License](#license)

## Requirements

- PHP 7.2 to 8.4 with `ext-json` (built in).
- Either the `curl` extension (used when loaded), or `allow_url_fopen` with `openssl` (PHP's HTTP streams). Nothing else.
- A Satelit API key for every route except the keyless ones (`sample()`, `products()`, `plans()`, `status()`, `stats()`, `movies()->board()`). Keys are made in the [console](https://developer.satelit.md/en/console/). Plans are on the [pricing page](https://developer.satelit.md/en/pricing/). To look at the data before choosing a plan, use the keyless `/v1/sample` and the console.

## Install

### With Composer, from the GitHub repository

Add the repository to your project's `composer.json`, then require the package:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/Satelit-Moldova/satelit-php" }
    ],
    "require": {
        "satelit-moldova/satelit-php": "^1.0"
    }
}
```

```sh
composer update satelit-moldova/satelit-php
```

```php
require __DIR__ . '/vendor/autoload.php';
```

### Without Composer (shared hosting, FTP)

Download the repository (Code → Download ZIP), upload the folder, and load its autoloader:

```php
require __DIR__ . '/satelit-php/src/autoload.php';
```

Only `src/` is needed on the server.

## Configure

```php
$satelit = new Satelit\Client($apiKey, [
    'lang'           => 'ro',     // ro (default), ru, en, uk: labels, texts, error messages
    'cache'          => new Satelit\Cache\FileCache('/path/outside/webroot/satelit-cache'),
    'timeout'        => 10,       // seconds per request, 1 to 10
    'retries'        => 3,        // 0 to 3; see Errors
    'stale_if_error' => true,     // with a cache: the last good copy when the API is down
    'user_agent'     => 'my-site/1.0',
    // 'transport'   => new Satelit\Http\StreamTransport(),  // force PHP streams
    // 'ttl'         => function ($path) { return 3600; },   // your own cache times
]);
$ru = $satelit->withLang('ru');  // a copy asking in Russian
```

Keep the key on the server: in an environment variable, or a config file outside the web root. Never print it in a page or send it to the browser. For a browser widget, call your own PHP endpoint that uses this client. The SDK sends the key only in the `Authorization: Bearer` header and never puts it in an exception message.

## Usage

Every method returns a `Satelit\Result`:

| Property | What it holds |
|---|---|
| `$r->data` | the answer, as arrays (`json_decode(..., true)`) |
| `$r->meta` | `credits`, `updatedAt`, `sources` (name, url, license), `attribution`, `notice`, `page` (`page`, `per`, `total`, `pages`), `next`, `hasMore`, and `raw` with every field as sent; `attributionLines()` gives the lines to show |
| `$r->rate` | `limit`, `remaining`, `reset` (requests a minute), `creditsCost`, `creditsLimit`, `creditsRemaining`, `creditsReset`, `retryAfter`; `null` when a header was not there |
| `$r->cached`, `$r->stale` | from the cache. The API failed and this is the last good copy |

A `Result` also reads like its data: `$r[0]['code']`, `foreach ($r as $row)`, `count($r)`.

```php
$fuel = $satelit->fuel()->board();
foreach ($fuel->data['networks'] as $n) {
    if ($n['key'] === $fuel->data['cheapest']['a95']) {
        printf("Cheapest A95: %s, %.2f lei/l\n", $n['name'], $n['prices']['a95']);
    }
}

$milk  = $satelit->prices()->search('lapte', ['in_stock' => true]);
$today = $satelit->movies()->today(['city' => 'Chișinău']);
$point = $satelit->geo()->search('str. Ismail 81, Chișinău');   // data is null when the town cannot be read
$any   = $satelit->get('/tariffs');                              // any route
```

Parameters are arrays with the API's names (see the [API reference](https://developer.satelit.md/en/reference/)): `null` and `''` are left out, `true`/`false` are sent as words, arrays are joined with commas (`'seen' => ['n_1', 'n_2']`).

### Attribution

Show `meta->attribution` wherever you show the data when it is not null (BNM's rates carry `Sursa: Banca Națională a Moldovei, bnm.md, CC BY 4.0`, OpenStreetMap rows their ODbL line), plus a small "Data: Satelit (satelit.md)" link; `$r->meta->attributionLines()` returns exactly those lines. With shop prices, show the shop's name with each offer (`$offer['store']['name']`). Escape every value you print (`htmlspecialchars`).

## Methods

`$satelit` is a `Satelit\Client`. Every method also takes a last `array $params` with the route's optional parameters. Credits per call are the API's, as `/v1/products` lists them.

| Group | Accessor | Methods | Routes (`/v1`) |
|---|---|---|---|
| Account | | `me()`, `products()`, `plans()`, `status()`, `stats()`, `sample()`, `get($path)`, `paginate($path)`, `feed()`, `export($product)` | `/me`, `/products`, `/plans`, `/status`, `/stats`, `/sample`, `/feed`, `/export/{product}` |
| Rates | `rates()` | `list()`, `history($code)` | `/rates`, `/rates/history` |
| Fuel | `fuel()` | `board()`, `stations()`, `eachStations()`, `history($fuel)` | `/fuel`, `/fuel/stations`, `/fuel/history` |
| Tariffs | `tariffs()` | `list()` | `/tariffs` |
| Indexes | `indexes()` | `basket()`, `moves()` | `/indexes/basket`, `/indexes/moves` |
| Law | `law()` | `acts()`, `eachActs()`, `act($id)`, `digests()`, `digest($date)` | `/law/acts`, `/law/acts/{id}`, `/law/digests`, `/law/digests/{date}` |
| News | `news()` | `list()`, `each()`, `now()`, `search($q)`, `day($date)`, `story($id)`, `categories()`, `regions()` | `/news`, `/news/now`, `/news/search`, `/news/days/{date}`, `/news/{id}`, `/news/categories`, `/news/regions` |
| News events | `news()` | `events()`, `eachEvents()`, `event($id)`, `eventComments($id)`, `outlets()`, `storyReaction($id)`, `storyComments($id)` | `/news/events`, `/news/events/{id}`, `/news/events/{id}/comments`, `/news/outlets`, `/news/{id}/reaction`, `/news/{id}/comments` |
| Places | `places()` | `list()`, `each()` | `/places` |
| Geo | `geo()` | `search($q)`, `streets($q)` | `/geo/search`, `/geo/streets` |
| Catalog | `catalog()` | `tree()` | `/catalog/tree` |
| Recipes | `recipes()` | `list()`, `get($slug)` | `/recipes`, `/recipes/{slug}` |
| Prices | `prices()` | `stores()`, `search($q)`, `eachSearch($q)`, `groups()`, `group($id)`, `item($id)`, `itemHistory($id)` | `/prices/stores`, `/prices/search`, `/prices/groups`, `/prices/groups/{id}`, `/prices/items/{id}`, `/prices/items/{id}/history` |
| Deals | `deals()` | `list()`, `each()` | `/deals` |
| Tours | `tours()` | `list()`, `each()`, `countries()`, `get($id)` | `/tours`, `/tours/countries`, `/tours/{id}` |
| Flights | `flights()` | `destinations()`, `destination($code)`, `board()` | `/flights/destinations`, `/flights/destinations/{code}`, `/flights/board` |
| Rest | `rest()` | `list()`, `each()` | `/rest` |
| Estate | `estate()` | `series()` | `/estate` |
| Power | `power()` | `day()`, `history()`, `bilateral()` | `/power`, `/power/history`, `/power/bilateral` |
| Market | `market()` | `categories()`, `prices()`, `history()`, `sellers()`, `seller($id)` | `/market/categories`, `/market/prices`, `/market/history`, `/market/sellers`, `/market/sellers/{id}` |
| Insurance | `insurance()` | `market()`, `insurers()`, `tariffs()` | `/insurance/market`, `/insurance/insurers`, `/insurance/tariffs` |
| Delivery | `delivery()` | `get()` | `/delivery` |
| Jokes | `jokes()` | `list()`, `today()`, `random()`, `topics()`, `get($id)` | `/jokes`, `/jokes/today`, `/jokes/random`, `/jokes/topics`, `/jokes/{id}` |
| Courts | `courts()` | `courts()`, `search($q)`, `hearings()`, `caseByNumber($number)`, `name($kind, $slug)`, `judge($slug)`, `stats()` | `/courts/courts`, `/courts/search`, `/courts/hearings`, `/courts/case`, `/courts/names/{kind}/{slug}`, `/courts/judges/{slug}`, `/courts/stats` |
| Citizenship | `citizenship()` | `lookup($q)`, `stats()`, `years()`, `year($slug)`, `orders()`, `order($slug)`, `oath()` | `/citizenship/lookup`, `/citizenship/stats`, `/citizenship/years`, `/citizenship/years/{slug}`, `/citizenship/orders`, `/citizenship/orders/{slug}`, `/citizenship/oath` |
| Companies | `companies()` | `search($q)`, `eachSearch($q)`, `stats()`, `get($idno)`, `contracts($idno)`, `licences()`, `companyLicences($idno)`, `notices()`, `companyNotices($idno)` | `/companies`, `/companies/stats`, `/companies/{idno}`, `/companies/{idno}/contracts`, `/companies/licences`, `/companies/{idno}/licences`, `/companies/notices`, `/companies/{idno}/notices` |
| Movies | `movies()` | `search($q)`, `title($id)`, `titleFull($id)`, `showtimes($id)`, `person($id)`, `discover()`, `trending()`, `inCinemas()`, `genres()`, `cinemas()`, `schedule($id)`, `today()`, `board()` | `/movies/search`, `/movies/titles/{id}`, `/movies/titles/{id}/full`, `/movies/titles/{id}/showtimes`, `/movies/people/{id}`, `/movies/discover`, `/movies/trending`, `/movies/cinema`, `/movies/genres`, `/movies/cinemas`, `/movies/cinemas/{id}/schedule`, `/movies/cinemas/today`, `/movies/board` |

The `each...()` methods are generators over every page (see below). Picture URLs in Movies answers (`https://api.satelit.md/v1/movies/img/...`) work in `<img>` as they are.

## Errors

Every exception extends `Satelit\Exception\SatelitException` (a `RuntimeException`). An error answer of the API is an `ApiException`, with a subclass per status and code:

| Class | When |
|---|---|
| `AuthenticationException` | 401 `auth_required` (no key), `key_invalid` |
| `PlanRequiredException` | 402 `plan_required`; `getChoose()` links the plans when the account has none |
| `ForbiddenException` | 403 `not_available`, `account_suspended` |
| `NotFoundException` | 404 `not_found` |
| `ValidationException` | 422 `invalid`; `getFields()` names the parameters |
| `RateLimitedException` | 429 `rate_limited` (after the retries) |
| `QuotaExceededException` | 429 `quota_exceeded`: the period's credits are used |
| `ExportRunningException` | 429 `export_running` |
| `TooManyRequestsException` | parent of the three 429 classes |
| `ServiceUnavailableException` | 503 `busy` |
| `ServerException` | another 5xx |
| `TransportException` | no answer: DNS, TLS, a timeout (not an `ApiException`) |
| `DecodeException` | an answer that is not the API's JSON |
| `ExportIncompleteException` | an export that ended without its `_end` line |

```php
try {
    $satelit->rates()->history('');
} catch (Satelit\Exception\ValidationException $e) {
    echo $e->getStatus(), ' ', $e->getErrorCode(), ' ', implode(',', $e->getFields());
    // 422 invalid code
} catch (Satelit\Exception\ApiException $e) {
    error_log($e->getMessage());   // "Satelit API 404 not_found: ... [/path]", never the key
} catch (Satelit\Exception\SatelitException $e) {
    // the API could not be reached
}
```

`ApiException` also has `getApiMessage()` (in the request's language), `getDocs()` (the error's page at [developer.satelit.md/en/errors](https://developer.satelit.md/en/errors/)), `getRetryAfter()`, `getRateInfo()` and `getPath()`. `getCode()` is the HTTP status.

**Retries.** Only two answers are tried again: 429 `rate_limited` and 503 with a `Retry-After` header, waiting what `Retry-After` says (at most 60 s), up to 3 times (`'retries' => 0..3`). 429 `quota_exceeded` and `export_running` are never retried, nor is anything else.

## Credits and caching

Each call costs credits of your plan's period (only a 2xx answer is charged). Most cost 1 to 5; **every Movies call costs 40** (`titleFull()` 55, `genres()` and `board()` 0). `$r->rate->creditsRemaining` and `me()` say what is left. See [rate limits](https://developer.satelit.md/en/rate-limits/) and the [pricing page](https://developer.satelit.md/en/pricing/).

Give the client a cache and it keeps each 2xx answer as long as its source changes (`Satelit\Cache\Ttl::forPath()`):

| Route | Kept |
|---|---|
| `/rates` | 3 h (BNM publishes once a working day) |
| `/fuel`, `/prices`, `/deals`, `/flights`, `/power`, `/law`, `/jokes` | 1 h |
| `/news` | 10 min |
| `/movies/titles/{id}`, `/full`, `/movies/people/{id}` | 24 h |
| `/movies/search`, `/discover`, `/trending` | 6 h |
| `/movies/cinemas/...`, `/movies/cinema`, showtimes | 2 h |
| `/movies/genres` | 7 days |
| `/tours`, `/indexes`, `/market`, `/companies`, `/citizenship`, `/recipes` | 6 h |
| `/tariffs`, `/rest`, `/estate`, `/places` | 12 h |
| `/delivery`, `/insurance`, `/geo`, `/catalog` | 24 h |
| `/me`, `/feed`, `/export` | never |

Included: `FileCache` (one small JSON file per entry, atomic writes, for hosting without APCu or Redis, put its folder outside the web root) and `ArrayCache` (one process). `Satelit\Cache\CacheInterface` is the part of PSR-16 the client uses, so any PSR-16 cache works as it is (Symfony Cache, Laravel's `Cache::store()`, ...). Keys look like `satelit_<sha1>` and include a hash of the API key, the path, the query and the language. A cache that throws is ignored: the call still goes to the API.

With a cache the client also keeps a last good copy (a day, or 24 TTLs). When the API cannot be reached, answers 5xx or 429 `rate_limited`, that copy is returned with `$r->stale === true`, so a page keeps showing data. A wrong key, a missing plan or a wrong parameter is never hidden this way. `'stale_if_error' => false` turns it off.

```console
$ php examples/cinema-today.php
2026-11-19, 2 cinemas
...
$ php examples/cinema-today.php
2026-11-19, 2 cinemas (from the cache, 0 credits)
```

## Pagination, feed and export

`each...()` and `paginate()` are generators that fetch page after page (each page costs its credits). Shop prices, deals, tours, rest places and companies stop at 5 pages, as the API does:

```php
foreach ($satelit->fuel()->eachStations(['network' => 'petrom']) as $station) {
    echo $station['name'], ' ', $station['prices']['diesel'], "\n";
}
foreach ($satelit->paginate('/law/acts', ['important' => true], 3) as $act) { ... }   // at most 3 pages
```

The change feed (Business and Enterprise plans) walks from a cursor. The generator's return value is the cursor to keep:

```php
$feed = $satelit->feed(['after' => $saved, 'types' => ['fuel.cap', 'rates.updated']]);
foreach ($feed as $event) {
    // $event['type'], $event['created_at'], $event['data']
}
$saved = $feed->getReturn();
```

An export (Enterprise plan) streams NDJSON row by row (curl writes to `php://temp`, so memory stays flat):

```php
$rows = $satelit->export('rates');
foreach ($rows as $row) { ... }
$end = $rows->getReturn();   // ['meta' => [...], 'rows' => 1234, 'complete' => true]
```

A stream cut before its `_end` line throws `ExportIncompleteException`. On other plans both throw `PlanRequiredException` when the loop starts, and nothing is charged.

## Examples

| File | What |
|---|---|
| [`examples/quickstart.php`](examples/quickstart.php) | EUR rate, cheapest A95, a price search |
| [`examples/rates-widget.php`](examples/rates-widget.php) | an HTML block of BNM rates for any page: file cache, escaped output, attribution, nothing shown when the API fails |
| [`examples/cinema-today.php`](examples/cinema-today.php) | today in a city's cinemas, cached 2 h (40 credits a call) |

`rates-widget.php`, live output:

```html
<table class="satelit-rates"><caption>Cursul oficial BNM, 2026-11-19</caption><tr><th scope="row">EUR</th><td>20,1411 lei</td></tr><tr><th scope="row">USD</th><td>18,3744 lei</td></tr><tr><th scope="row">RON</th><td>3,7671 lei</td></tr></table><p class="satelit-source"><small>Sursa: Banca Națională a Moldovei, bnm.md, CC BY 4.0<br>Aceleași date sunt gratuite la bnm.md.<br>Data: Satelit (satelit.md) &middot; <a href="https://satelit.md" rel="noopener">satelit.md</a></small></p>
```

With a revoked key it prints nothing and logs one line:

```text
satelit rates widget: Satelit API 401 key_invalid: Cheia API nu e validă sau a fost revocată. [/rates]
```

## Tests

```sh
composer update && vendor/bin/phpunit        # offline: a mock transport and a local php -S server
SATELIT_API_KEY=sat_live_... php tests/live-smoke.php       # live, about 50 credits
SATELIT_API_KEY_FILE=/run/secret php tests/live-smoke.php   # or the key in a file
```

The offline suite (54 tests) reads the recorded live answers of all 53 sampled routes (`tests/fixtures/samples.json`) through their methods, calls each of the 108 service methods, and covers every exception class, the retry rules, the caches, the stale copy, pagination, the feed and exports. `TransportTest` runs both transports (curl and streams) against PHP's built-in server on 127.0.0.1: headers, redirects, errors, a 5 000-line export and a timeout. No request leaves the machine.

`tests/live-smoke.php` checks the real API: plan, rates, fuel, prices, Movies with the cache (the second call costs 0), geocoding, pagination, the 401/404/422 answers, the feed and export on a plan without them, and the same call over curl and over streams.

## Tested with

| | Version |
|---|---|
| PHP 7.2.34 (curl 7.64.0), PHPUnit 8.5.57 | offline suite, live smoke, examples (official `php:7.2-cli` image) |
| PHP 7.4.33 (curl 7.74.0), PHPUnit 9.6.39 | offline suite (`php:7.4-cli`) |
| PHP 8.1.34 (curl 8.14.1), PHPUnit 9.6.39 | offline suite (`php:8.1-cli`) |
| PHP 8.3.36 (curl 8.14.1), PHPUnit 9.6.39 | offline suite, live smoke (`php:8.3-cli`) |
| PHP 8.4.27 (curl 8.14.1), PHPUnit 9.6.39 | offline suite, no deprecation with `error_reporting=-1` (`php:8.4-cli`) |
| Composer 2.11.2 | `composer install` of `^1.0` from a VCS repository, then used on PHP 7.2 and 8.4 (`composer:2` image) |
| API | `https://api.satelit.md/v1`, 19 November 2026 |

## Troubleshooting

| Symptom | Cause and fix |
|---|---|
| `AuthenticationException` `auth_required` | The key is empty in that process (`getenv()` sees nothing under PHP-FPM unless the pool passes it: use `env[SATELIT_API_KEY]` or a config file). |
| `AuthenticationException` `key_invalid` | The key was revoked or mistyped: make a new one in the [console](https://developer.satelit.md/en/console/). |
| `PlanRequiredException` | No plan, or the plan lacks that route (the feed, exports, older history). |
| `QuotaExceededException` | The period's credits are used; `me()` shows when they reset. Add a cache. |
| `TransportException` "allow_url_fopen is off and curl is missing" | The host allows neither: ask for the curl extension. |
| `TransportException` with an SSL error | The host's CA bundle is old: update it or set `curl.cainfo` / `openssl.cafile` in `php.ini`. |
| `RuntimeException` "Cannot create the cache directory" | The folder given to `FileCache` is not writable by PHP. |
| Movies cost a lot | 40 credits a call: give the client a cache. |

## Pe scurt (română)

`satelit-php` este clientul PHP pentru API-ul de date Satelit: cursul BNM, carburanți, tarife, legislație, știri, prețuri din magazine, filme și altele. Merge pe PHP 7.2 până la 8.4, fără dependențe (curl sau, fără el, fluxurile HTTP ale PHP), cu Composer sau cu un singur `require 'src/autoload.php'` pe un hosting fără Composer. Cheia stă doar pe server ([consola](https://developer.satelit.md/en/console/)). Planurile sunt pe [pagina de prețuri](https://developer.satelit.md/en/pricing/). Afișați `meta->attribution` lângă date și „Date: Satelit (satelit.md)”. Un apel Movies costă 40 de credite: dați clientului un cache (`FileCache`), care păstrează fiecare răspuns cât se schimbă sursa și, când API-ul nu răspunde, ultima copie bună. Erorile sunt `ApiException` cu o subclasă pe fiecare cod. Se reîncearcă doar 429 `rate_limited` și 503 cu `Retry-After`, de cel mult 3 ori.

## Links

[Docs](https://developer.satelit.md/en/docs/) · [API reference](https://developer.satelit.md/en/reference/) · [Errors](https://developer.satelit.md/en/errors/) · [Rate limits](https://developer.satelit.md/en/rate-limits/) · [Authentication](https://developer.satelit.md/en/authentication/) · [Webhooks](https://developer.satelit.md/en/webhooks/) · [OpenAPI](https://api.satelit.md/openapi.json) · [Pricing](https://developer.satelit.md/en/pricing/) · [Console](https://developer.satelit.md/en/console/)

## License

[MIT](LICENSE), Copyright (c) 2026 Satelit (satelit.md). The data has its own terms: each answer's `meta.sources` names its source and licence.
