# Changelog

## 1.0.0 (2026-11-19)

- First release: a client for every route of the Satelit data API `/v1` (rates, fuel, tariffs, indexes, law, news and news events, places, geo, catalog, recipes, prices, deals, tours, flights, rest, estate, power, market, insurance, delivery, jokes, courts, citizenship, companies, movies, account).
- PHP 7.2 to 8.4, no dependencies: curl, or PHP's HTTP streams when curl is missing. Composer (PSR-4) or a single `require 'src/autoload.php'`.
- `Result` with `Meta` and `RateInfo` on every call; `ApiException` subclasses per status and code.
- Retries only for 429 `rate_limited` and 503 with `Retry-After`, at most 3.
- A PSR-16-like cache interface, `FileCache` and `ArrayCache`, per-product TTLs and a last good copy when the API is down.
- Generators for pages, the change feed and NDJSON exports.
