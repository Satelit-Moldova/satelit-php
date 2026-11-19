<?php

namespace Satelit;

use Satelit\Cache\Ttl;
use Satelit\Exception\ApiException;
use Satelit\Exception\DecodeException;
use Satelit\Exception\ExportIncompleteException;
use Satelit\Exception\SatelitException;
use Satelit\Exception\TransportException;
use Satelit\Http\CurlTransport;
use Satelit\Http\HttpResponse;
use Satelit\Http\StreamTransport;
use Satelit\Http\TransportInterface;

/**
 * Client of the Satelit data API (https://api.satelit.md/v1).
 *
 *     $satelit = new Satelit\Client(getenv('SATELIT_API_KEY'), [
 *         'lang'  => 'ro',
 *         'cache' => new Satelit\Cache\FileCache(__DIR__ . '/cache'),
 *     ]);
 *     $rates = $satelit->rates()->list();
 *     echo $rates[0]['code'], ' ', $rates[0]['value'];
 *     echo $rates->meta->attribution;
 *
 * Options: lang (ro, ru, en, uk), base_url, timeout (seconds, at most 10),
 * retries (0..3), transport (a TransportInterface), cache (CacheInterface or
 * any PSR-16 cache), ttl (callable(string $path): int seconds), stale_if_error
 * (default true with a cache: when the API is down, busy or rate limited,
 * return the last good copy, kept a day or 24 TTLs, with $result->stale true),
 * user_agent, sleep (callable(int $seconds), for tests).
 */
class Client
{
    const VERSION = '1.0.0';
    const BASE_URL = 'https://api.satelit.md/v1';
    const MAX_RETRIES = 3;
    const MAX_RETRY_WAIT = 60;

    /** @var string */
    private $key;
    /** @var string */
    private $baseUrl;
    /** @var string */
    private $lang;
    /** @var float */
    private $timeout;
    /** @var int */
    private $retries;
    /** @var TransportInterface */
    private $transport;
    /** @var object|null */
    private $cache;
    /** @var callable */
    private $ttl;
    /** @var string */
    private $userAgent;
    /** @var callable */
    private $sleep;
    /** @var bool */
    private $staleIfError;
    /** @var array<string,object> */
    private $services = array();

    /**
     * @param string|null $apiKey empty for the keyless routes (sample, products, plans, status, stats)
     * @param array $options see the class comment
     */
    public function __construct($apiKey, array $options = array())
    {
        $this->key = trim((string) $apiKey);
        $this->baseUrl = rtrim(isset($options['base_url']) ? (string) $options['base_url'] : self::BASE_URL, '/');
        $this->lang = isset($options['lang']) ? (string) $options['lang'] : 'ro';
        $this->timeout = isset($options['timeout']) ? min(10.0, max(1.0, (float) $options['timeout'])) : 10.0;
        $this->retries = isset($options['retries']) ? max(0, min(self::MAX_RETRIES, (int) $options['retries'])) : self::MAX_RETRIES;
        $this->transport = isset($options['transport']) ? $options['transport'] : self::defaultTransport();
        if (!$this->transport instanceof TransportInterface) {
            throw new \InvalidArgumentException('transport must implement Satelit\Http\TransportInterface');
        }
        $this->cache = isset($options['cache']) ? $options['cache'] : null;
        if ($this->cache !== null && !(method_exists($this->cache, 'get') && method_exists($this->cache, 'set'))) {
            throw new \InvalidArgumentException('cache must have get() and set() (Satelit\Cache\CacheInterface or PSR-16)');
        }
        $this->ttl = isset($options['ttl']) && is_callable($options['ttl']) ? $options['ttl'] : array(Ttl::class, 'forPath');
        $this->userAgent = 'satelit-php/' . self::VERSION . ' (+https://github.com/Satelit-Moldova/satelit-php; PHP ' . PHP_VERSION . ')';
        if (!empty($options['user_agent'])) {
            $this->userAgent = $options['user_agent'] . ' ' . $this->userAgent;
        }
        $this->staleIfError = !isset($options['stale_if_error']) || (bool) $options['stale_if_error'];
        $this->sleep = isset($options['sleep']) && is_callable($options['sleep']) ? $options['sleep'] : 'sleep';
    }

    /**
     * curl when loaded, else PHP's HTTP streams.
     *
     * @return TransportInterface
     */
    public static function defaultTransport()
    {
        if (function_exists('curl_init')) {
            return new CurlTransport();
        }
        return new StreamTransport();
    }

    /**
     * A copy of this client that asks in another language.
     *
     * @param string $lang ro, ru, en, uk
     * @return Client
     */
    public function withLang($lang)
    {
        $copy = clone $this;
        $copy->lang = (string) $lang;
        $copy->services = array();
        return $copy;
    }

    /** @return string */
    public function getLang()
    {
        return $this->lang;
    }

    // Product groups -------------------------------------------------------

    /** @return Service\Rates */
    public function rates() { return $this->service('Rates'); }
    /** @return Service\Fuel */
    public function fuel() { return $this->service('Fuel'); }
    /** @return Service\Tariffs */
    public function tariffs() { return $this->service('Tariffs'); }
    /** @return Service\Indexes */
    public function indexes() { return $this->service('Indexes'); }
    /** @return Service\Law */
    public function law() { return $this->service('Law'); }
    /** @return Service\News */
    public function news() { return $this->service('News'); }
    /** @return Service\Places */
    public function places() { return $this->service('Places'); }
    /** @return Service\Recipes */
    public function recipes() { return $this->service('Recipes'); }
    /** @return Service\Prices */
    public function prices() { return $this->service('Prices'); }
    /** @return Service\Deals */
    public function deals() { return $this->service('Deals'); }
    /** @return Service\Tours */
    public function tours() { return $this->service('Tours'); }
    /** @return Service\Flights */
    public function flights() { return $this->service('Flights'); }
    /** @return Service\Rest */
    public function rest() { return $this->service('Rest'); }
    /** @return Service\Estate */
    public function estate() { return $this->service('Estate'); }
    /** @return Service\Power */
    public function power() { return $this->service('Power'); }
    /** @return Service\Market */
    public function market() { return $this->service('Market'); }
    /** @return Service\Jokes */
    public function jokes() { return $this->service('Jokes'); }
    /** @return Service\Courts */
    public function courts() { return $this->service('Courts'); }
    /** @return Service\Citizenship */
    public function citizenship() { return $this->service('Citizenship'); }
    /** @return Service\Companies */
    public function companies() { return $this->service('Companies'); }
    /** @return Service\Delivery */
    public function delivery() { return $this->service('Delivery'); }
    /** @return Service\Insurance */
    public function insurance() { return $this->service('Insurance'); }
    /** @return Service\Geo */
    public function geo() { return $this->service('Geo'); }
    /** @return Service\Catalog */
    public function catalog() { return $this->service('Catalog'); }
    /** @return Service\Movies */
    public function movies() { return $this->service('Movies'); }

    private function service($name)
    {
        if (!isset($this->services[$name])) {
            $class = 'Satelit\\Service\\' . $name;
            $this->services[$name] = new $class($this);
        }
        return $this->services[$name];
    }

    // Account and catalogue ------------------------------------------------

    /** The key's plan, credits and rate. 0 credits. @return Result */
    public function me() { return $this->get('/me'); }
    /** The products and their sources. Keyless. @return Result */
    public function products() { return $this->get('/products'); }
    /** The plans (link https://developer.satelit.md/en/pricing/ rather than copying prices). Keyless. @return Result */
    public function plans() { return $this->get('/plans'); }
    /** Whether each product is fresh. Keyless. @return Result */
    public function status() { return $this->get('/status'); }
    /** Counts of what the API holds. Keyless. @return Result */
    public function stats() { return $this->get('/stats'); }
    /** A taste of the data, keyless, 60 a minute per IP. @return Result */
    public function sample() { return $this->get('/sample'); }

    /**
     * Walks the change feed from a cursor, oldest first (Business and
     * Enterprise; others get PlanRequiredException). It ends when the feed
     * has nothing more now; the generator's return value is the cursor to
     * keep for the next run:
     *
     *     $feed = $satelit->feed(['after' => $saved, 'types' => ['fuel.cap']]);
     *     foreach ($feed as $event) { ... }
     *     $saved = $feed->getReturn();
     *
     * @param array $params after, types (array or comma list), limit (≤ 200)
     * @return \Generator<int,array>
     */
    public function feed(array $params = array())
    {
        $cursor = isset($params['after']) ? (string) $params['after'] : null;
        while (true) {
            $q = $params;
            if ($cursor !== null) {
                $q['after'] = $cursor;
            }
            $res = $this->get('/feed', $q);
            foreach ((array) $res->data as $event) {
                yield $event;
            }
            if ($res->meta->next !== null && $res->meta->next !== '') {
                $cursor = $res->meta->next;
            }
            if (!$res->meta->hasMore || !$res->data) {
                return $cursor;
            }
        }
    }

    /**
     * Streams /export/{product} as decoded rows (Enterprise). The first line's
     * _meta and the _end counts are the generator's return value:
     * ['meta' => array, 'rows' => int, 'complete' => bool]. A stream without
     * _end throws ExportIncompleteException after the last row.
     *
     * @param string $product news, law, places, rates, fuel, prices, ...
     * @return \Generator<int,array>
     */
    public function export($product)
    {
        $path = '/export/' . rawurlencode($product);
        $res = $this->send($path, $this->query(array()), true);
        $fp = $res->stream;
        $meta = null;
        $end = null;
        try {
            while (($line = fgets($fp)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $row = json_decode($line, true);
                if (!is_array($row)) {
                    throw new DecodeException('An export line is not JSON.');
                }
                if (isset($row['_meta']) && count($row) === 1) {
                    $meta = $row['_meta'];
                    continue;
                }
                if (isset($row['_end']) && count($row) === 1) {
                    $end = $row['_end'];
                    break;
                }
                yield $row;
            }
        } finally {
            fclose($fp);
        }
        if ($end === null) {
            throw new ExportIncompleteException('The export of ' . $product . ' ended without _end: it was cut.');
        }
        return array(
            'meta' => $meta,
            'rows' => isset($end['rows']) ? (int) $end['rows'] : 0,
            'complete' => !empty($end['complete']),
        );
    }

    /**
     * Every item of a paged list, page after page (each page costs its
     * credits). Stops after meta.page.pages, on an empty page, or at $maxPages.
     *
     * @param string $path
     * @param array $params
     * @param int $maxPages 0: no limit
     * @return \Generator<int,array>
     */
    public function paginate($path, array $params = array(), $maxPages = 0)
    {
        $page = isset($params['page']) ? max(1, (int) $params['page']) : 1;
        $fetched = 0;
        while (true) {
            $params['page'] = $page;
            $res = $this->get($path, $params);
            $fetched++;
            $items = is_array($res->data) ? $res->data : array();
            foreach ($items as $item) {
                yield $item;
            }
            $pages = isset($res->meta->page['pages']) ? (int) $res->meta->page['pages'] : 0;
            if (!$items || $page >= $pages || ($maxPages > 0 && $fetched >= $maxPages)) {
                return;
            }
            $page++;
        }
    }

    /**
     * Any GET route. $path is relative to /v1, such as "/rates".
     *
     * @param string $path
     * @param array $params null and '' are left out; true/false sent as words; arrays joined with commas
     * @return Result
     * @throws ApiException on an error answer
     * @throws TransportException when no answer came
     */
    public function get($path, array $params = array())
    {
        $path = '/' . ltrim($path, '/');
        $query = $this->query($params);
        $ttl = (int) call_user_func($this->ttl, $path);
        $key = null;
        if ($this->cache !== null && $ttl > 0) {
            $key = 'satelit_' . sha1(substr(sha1($this->key), 0, 12) . ' ' . $this->baseUrl . $path . '?' . $query);
            $hit = $this->fromCache($key);
            if ($hit !== null) {
                return $hit;
            }
        }
        try {
            $res = $this->send($path, $query, false);
        } catch (SatelitException $e) {
            // API down, busy or over its rate: an older copy beats nothing.
            $stale = ($key !== null && $this->staleIfError && self::isOutage($e)) ? $this->fromCache($key . '_stale') : null;
            if ($stale !== null) {
                $stale->stale = true;
                return $stale;
            }
            throw $e;
        }
        $decoded = json_decode($res->body, true);
        if (!is_array($decoded) || !array_key_exists('data', $decoded)) {
            throw new DecodeException('The answer of ' . $path . ' is not the API JSON.');
        }
        if ($key !== null) {
            $this->toCache($key, $res->body, $ttl);
            if ($this->staleIfError) {
                $this->toCache($key . '_stale', $res->body, max($ttl * 24, Ttl::DAY));
            }
        }
        return $this->result($decoded, RateInfo::fromResponse($res), false);
    }

    /**
     * An outage is no answer, a 5xx or a 429 rate_limited: not a wrong key,
     * a missing plan or a wrong parameter, which a cached copy would hide.
     */
    private static function isOutage(SatelitException $e)
    {
        if ($e instanceof TransportException || $e instanceof DecodeException) {
            return true;
        }
        return $e instanceof ApiException && ($e->getStatus() >= 500 || $e->getErrorCode() === 'rate_limited');
    }

    /** @return Result|null */
    private function fromCache($key)
    {
        try {
            $hit = $this->cache->get($key);
        } catch (\Exception $e) {
            return null; // a broken cache must not break the page
        }
        if (!is_string($hit) || $hit === '') {
            return null;
        }
        $decoded = json_decode($hit, true);
        if (!is_array($decoded) || !array_key_exists('data', $decoded)) {
            return null;
        }
        $ri = new RateInfo();
        $ri->statusCode = 200;
        return $this->result($decoded, $ri, true);
    }

    private function toCache($key, $body, $ttl)
    {
        try {
            $this->cache->set($key, $body, $ttl);
        } catch (\Exception $e) {
            // ignore: the answer is still returned
        }
    }

    private function result(array $decoded, RateInfo $ri, $cached)
    {
        $meta = Meta::fromArray(isset($decoded['meta']) && is_array($decoded['meta']) ? $decoded['meta'] : array());
        return new Result(array_key_exists('data', $decoded) ? $decoded['data'] : null, $meta, $ri, $cached);
    }

    /**
     * @param array $params
     * @return string
     */
    private function query(array $params)
    {
        if (!isset($params['lang']) && $this->lang !== '') {
            $params['lang'] = $this->lang;
        }
        $out = array();
        foreach ($params as $name => $value) {
            if ($value === null || $value === '' || $value === array()) {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_array($value)) {
                $value = implode(',', $value);
            }
            $out[$name] = (string) $value;
        }
        ksort($out);
        return http_build_query($out, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Sends with the retry rules: only 429 rate_limited and 503 with
     * Retry-After, at most $this->retries times; never quota_exceeded.
     *
     * @return HttpResponse a 2xx answer
     */
    private function send($path, $query, $stream)
    {
        $url = $this->baseUrl . $path . ($query !== '' ? '?' . $query : '');
        $headers = array(
            'Accept' => $stream ? 'application/x-ndjson' : 'application/json',
            'User-Agent' => $this->userAgent,
        );
        if ($this->key !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->key;
        }
        for ($attempt = 0; ; $attempt++) {
            $res = $stream
                ? $this->transport->stream($url, $headers, $this->timeout)
                : $this->transport->get($url, $headers, $this->timeout);
            if ($res->status >= 200 && $res->status < 300) {
                return $res;
            }
            $error = $this->error($res, $path);
            if ($res->stream !== null) {
                fclose($res->stream);
            }
            $retryable = ($error->getStatus() === 429 && $error->getErrorCode() === 'rate_limited')
                || ($error->getStatus() === 503 && $error->getRetryAfter() !== null && $error->getRetryAfter() > 0);
            if (!$retryable || $attempt >= $this->retries) {
                throw $error;
            }
            $wait = $error->getRetryAfter();
            if ($wait === null || $wait <= 0) {
                $wait = 1 << $attempt;
            }
            call_user_func($this->sleep, min($wait, self::MAX_RETRY_WAIT));
        }
    }

    private function error(HttpResponse $res, $path)
    {
        $body = $res->contents();
        $decoded = json_decode($body, true);
        $ri = RateInfo::fromResponse($res);
        if (is_array($decoded) && isset($decoded['error']['code'])) {
            $e = $decoded['error'];
            return ApiException::create(
                $res->status,
                (string) $e['code'],
                isset($e['message']) ? (string) $e['message'] : '',
                $e,
                $ri,
                $path
            );
        }
        $text = trim(strip_tags(substr($body, 0, 200)));
        return ApiException::create($res->status, 'http_' . $res->status, $text, array(), $ri, $path);
    }
}
