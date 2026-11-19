<?php

namespace Satelit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Satelit\Cache\ArrayCache;
use Satelit\Cache\FileCache;
use Satelit\Cache\Ttl;
use Satelit\Client;
use Satelit\Exception\ApiException;
use Satelit\Exception\AuthenticationException;
use Satelit\Exception\DecodeException;
use Satelit\Exception\ExportIncompleteException;
use Satelit\Exception\ExportRunningException;
use Satelit\Exception\NotFoundException;
use Satelit\Exception\PlanRequiredException;
use Satelit\Exception\QuotaExceededException;
use Satelit\Exception\RateLimitedException;
use Satelit\Exception\SatelitException;
use Satelit\Exception\ServerException;
use Satelit\Exception\ServiceUnavailableException;
use Satelit\Exception\TooManyRequestsException;
use Satelit\Exception\TransportException;
use Satelit\Exception\ValidationException;
use Satelit\Http\HttpResponse;
use Satelit\Http\MockTransport;
use Satelit\Tests\Fixtures;

final class ClientTest extends TestCase
{
    public function testHeadersLangAndUserAgent()
    {
        list($c, $t) = Fixtures::client(array('lang' => 'en', 'user_agent' => 'myapp/2'));
        $c->rates()->list();
        $req = $t->requests[0];
        $this->assertSame('Bearer sat_live_test', $req['headers']['Authorization']);
        $this->assertSame('application/json', $req['headers']['Accept']);
        $this->assertStringStartsWith('myapp/2 satelit-php/1.0.0', $req['headers']['User-Agent']);
        $this->assertStringEndsWith('?lang=en', $req['url']);
        $this->assertLessThanOrEqual(10, $req['timeout']);

        $c->withLang('ru')->rates()->list();
        $this->assertStringEndsWith('?lang=ru', $t->requests[1]['url']);
        $this->assertSame('en', $c->getLang());
    }

    public function testKeylessSendsNoAuthorization()
    {
        $t = Fixtures::transport();
        $c = new Client('', array('transport' => $t, 'base_url' => 'https://api.test/v1'));
        $c->sample();
        $this->assertArrayNotHasKey('Authorization', $t->requests[0]['headers']);
    }

    public function testTimeoutIsCappedAtTenSeconds()
    {
        list($c, $t) = Fixtures::client(array('timeout' => 60));
        $c->rates()->list();
        $this->assertSame(10.0, $t->requests[0]['timeout']);
    }

    public function testQueryEncoding()
    {
        list($c, $t) = Fixtures::queued();
        $t->push(200, Fixtures::ok(array()));
        $c->get('/news/now', array(
            'seen' => array('n_1', 'n_2'),
            'social_only' => false,
            'all' => true,
            'region' => null,
            'slot' => '',
            'q' => 'lapte bio',
        ));
        $url = $t->requests[0]['url'];
        $this->assertSame('https://api.test/v1/news/now?all=true&lang=ro&q=lapte%20bio&seen=n_1%2Cn_2&social_only=false', $url);
    }

    public function testPathSegmentsAreEscaped()
    {
        list($c, $t) = Fixtures::queued();
        $t->push(200, Fixtures::ok(array()));
        $c->recipes()->get('a/b?c');
        $this->assertStringStartsWith('https://api.test/v1/recipes/a%2Fb%3Fc?', $t->requests[0]['url']);
        $this->expectException(\InvalidArgumentException::class);
        $c->recipes()->get('');
    }

    /**
     * @dataProvider errorCases
     */
    public function testErrorClasses($status, $code, $class)
    {
        list($c, $t) = Fixtures::queued(array('retries' => 0));
        $t->push($status, Fixtures::err($code, 'msg', array('fields' => array('code'))), array('X-Credits-Cost' => '0', 'Retry-After' => '5'));
        try {
            $c->rates()->history('');
            $this->fail('no exception');
        } catch (ApiException $e) {
            $this->assertInstanceOf($class, $e);
            $this->assertInstanceOf(SatelitException::class, $e);
            $this->assertSame($status, $e->getStatus());
            $this->assertSame($status, $e->getCode());
            $this->assertSame($code, $e->getErrorCode());
            $this->assertSame('msg', $e->getApiMessage());
            $this->assertSame(array('code'), $e->getFields());
            $this->assertSame('/rates/history', $e->getPath());
            $this->assertSame(0, $e->getRateInfo()->creditsCost);
            $this->assertStringContainsString('errors/#' . $code, $e->getDocs());
            $this->assertStringNotContainsString('sat_live_test', $e->getMessage());
        }
    }

    public function errorCases()
    {
        return array(
            array(401, 'key_invalid', AuthenticationException::class),
            array(402, 'plan_required', PlanRequiredException::class),
            array(403, 'not_available', \Satelit\Exception\ForbiddenException::class),
            array(404, 'not_found', NotFoundException::class),
            array(422, 'invalid', ValidationException::class),
            array(429, 'rate_limited', RateLimitedException::class),
            array(429, 'quota_exceeded', QuotaExceededException::class),
            array(429, 'export_running', ExportRunningException::class),
            array(429, 'other', TooManyRequestsException::class),
            array(503, 'busy', ServiceUnavailableException::class),
            array(500, 'oops', ServerException::class),
            array(405, 'method_not_allowed', ApiException::class),
        );
    }

    public function testPlanRequiredCarriesChoose()
    {
        list($c, $t) = Fixtures::queued();
        $t->push(402, Fixtures::err('plan_required', 'Alege un plan', array('choose' => 'https://developer.satelit.md/ro/pricing/')));
        try {
            $c->rates()->list();
            $this->fail('no exception');
        } catch (PlanRequiredException $e) {
            $this->assertSame('https://developer.satelit.md/ro/pricing/', $e->getChoose());
        }
    }

    public function testNonJsonErrorAndBody()
    {
        list($c, $t) = Fixtures::queued(array('retries' => 0));
        $t->push(502, '<html><body>Bad gateway</body></html>');
        try {
            $c->rates()->list();
            $this->fail('no exception');
        } catch (ServerException $e) {
            $this->assertSame('http_502', $e->getErrorCode());
            $this->assertStringContainsString('Bad gateway', $e->getApiMessage());
        }
        $t->push(200, 'not json');
        $this->expectException(DecodeException::class);
        $c->rates()->list();
    }

    /**
     * @dataProvider retryCases
     */
    public function testRetryRules($status, $code, $retryAfter, $failures, $wantOk, $wantCalls)
    {
        $n = 0;
        $waits = array();
        $t = new MockTransport(function () use (&$n, $status, $code, $retryAfter, $failures) {
            $n++;
            if ($n <= $failures) {
                $h = $retryAfter === null ? array() : array('Retry-After' => (string) $retryAfter);
                return new HttpResponse($status, $h, json_encode(Fixtures::err($code)));
            }
            return new HttpResponse(200, array(), json_encode(Fixtures::ok(array(array('code' => 'EUR')))));
        });
        $c = new Client('k', array('transport' => $t, 'sleep' => function ($s) use (&$waits) {
            $waits[] = $s;
        }));
        $ok = true;
        try {
            $c->rates()->list();
        } catch (ApiException $e) {
            $ok = false;
        }
        $this->assertSame($wantOk, $ok);
        $this->assertSame($wantCalls, $n);
        if ($wantCalls > 1 && $retryAfter !== null) {
            $this->assertSame(min($retryAfter, 60), $waits[0]);
        }
    }

    public function retryCases()
    {
        return array(
            'rate_limited retried' => array(429, 'rate_limited', 2, 2, true, 3),
            'rate_limited gives up after 3 retries' => array(429, 'rate_limited', 1, 10, false, 4),
            'wait capped at 60 s' => array(429, 'rate_limited', 3600, 1, true, 2),
            'quota_exceeded never retried' => array(429, 'quota_exceeded', 30, 10, false, 1),
            'export_running not retried' => array(429, 'export_running', 30, 10, false, 1),
            'busy with Retry-After retried' => array(503, 'busy', 2, 1, true, 2),
            'busy without Retry-After not retried' => array(503, 'busy', null, 10, false, 1),
            '404 not retried' => array(404, 'not_found', null, 10, false, 1),
        );
    }

    public function testRetriesOptionIsBounded()
    {
        $n = 0;
        $t = new MockTransport(function () use (&$n) {
            $n++;
            return new HttpResponse(429, array('Retry-After' => '1'), json_encode(Fixtures::err('rate_limited')));
        });
        $c = new Client('k', array('transport' => $t, 'retries' => 99, 'sleep' => function () {
        }));
        try {
            $c->rates()->list();
        } catch (RateLimitedException $e) {
        }
        $this->assertSame(4, $n);
    }

    public function testCacheSavesMovieCalls()
    {
        $cache = new ArrayCache();
        list($c, $t) = Fixtures::client(array('cache' => $cache));
        $a = $c->movies()->today(array('city' => 'Chișinău'));
        $b = $c->movies()->today(array('city' => 'Chișinău'));
        $this->assertCount(1, $t->requests);
        $this->assertFalse($a->cached);
        $this->assertTrue($b->cached);
        $this->assertSame($a->data, $b->data);
        $this->assertSame($a->meta->credits, $b->meta->credits);
        $c->movies()->today(array('city' => 'Bălți'));
        $c->withLang('en')->movies()->today(array('city' => 'Chișinău'));
        $this->assertCount(3, $t->requests, 'another query or language must miss');
        $c->me();
        $c->me();
        $this->assertCount(5, $t->requests, '/me is never cached');
    }

    public function testCacheKeysDifferPerApiKey()
    {
        $cache = new ArrayCache();
        $t = Fixtures::transport();
        $one = new Client('key-one', array('transport' => $t, 'cache' => $cache));
        $two = new Client('key-two', array('transport' => $t, 'cache' => $cache));
        $one->rates()->list();
        $two->rates()->list();
        $this->assertCount(2, $t->requests);
    }

    public function testStaleCopyWhenTheApiIsDown()
    {
        $cache = new ArrayCache();
        $down = false;
        $t = new MockTransport(function () use (&$down) {
            if ($down) {
                throw new TransportException('Connection refused');
            }
            return new HttpResponse(200, array(), json_encode(Fixtures::ok(array(array('code' => 'EUR', 'value' => 20.14)))));
        });
        $ttl = function () {
            return 60;
        };
        $c = new Client('k', array('transport' => $t, 'cache' => $cache, 'ttl' => $ttl));
        $c->rates()->list();
        // Expire the fresh copy, keep the stale one.
        $key = null;
        $ref = new \ReflectionProperty($cache, 'items');
        $ref->setAccessible(true);
        $items = $ref->getValue($cache);
        foreach (array_keys($items) as $k) {
            if (substr($k, -6) !== '_stale') {
                unset($items[$k]);
            }
        }
        $ref->setValue($cache, $items);
        $down = true;
        $r = $c->rates()->list();
        $this->assertTrue($r->stale);
        $this->assertTrue($r->cached);
        $this->assertSame(20.14, $r[0]['value']);

        $strict = new Client('k', array('transport' => $t, 'cache' => new ArrayCache(), 'stale_if_error' => false));
        $this->expectException(TransportException::class);
        $strict->rates()->list();
    }

    public function testStaleCopyNeverHidesAKeyOrPlanError()
    {
        $cache = new ArrayCache();
        $status = 200;
        $t = new MockTransport(function () use (&$status) {
            if ($status !== 200) {
                return new HttpResponse($status, array(), json_encode(Fixtures::err('key_invalid')));
            }
            return new HttpResponse(200, array(), json_encode(Fixtures::ok(array())));
        });
        $c = new Client('k', array('transport' => $t, 'cache' => $cache, 'ttl' => function () {
            return 1;
        }));
        $c->rates()->list();
        $cache->delete(array_keys($this->items($cache))[0]);
        $status = 401;
        $this->expectException(AuthenticationException::class);
        $c->rates()->list();
    }

    private function items(ArrayCache $cache)
    {
        $ref = new \ReflectionProperty($cache, 'items');
        $ref->setAccessible(true);
        return $ref->getValue($cache);
    }

    public function testAnyPsr16LikeCacheAndBrokenCache()
    {
        $psr = new class {
            public $store = array();
            public function get($key, $default = null)
            {
                return isset($this->store[$key]) ? $this->store[$key] : $default;
            }
            public function set($key, $value, $ttl = null)
            {
                $this->store[$key] = $value;
                return true;
            }
        };
        list($c, $t) = Fixtures::client(array('cache' => $psr));
        $c->fuel()->board();
        $c->fuel()->board();
        $this->assertCount(1, $t->requests);
        foreach (array_keys($psr->store) as $k) {
            $this->assertSame(1, preg_match('/^satelit_[0-9a-f]{40}(_stale)?$/', $k), 'PSR-16 safe key');
        }

        $broken = new class {
            public function get($key, $default = null)
            {
                throw new \RuntimeException('cache down');
            }
            public function set($key, $value, $ttl = null)
            {
                throw new \RuntimeException('cache down');
            }
        };
        list($c2) = Fixtures::client(array('cache' => $broken));
        $this->assertSame(28.08, $c2->fuel()->board()->data['cap']['a95']);
    }

    public function testFileCache()
    {
        $dir = sys_get_temp_dir() . '/satelit-test-' . uniqid();
        $cache = new FileCache($dir);
        $this->assertTrue($cache->set('k1', '{"data":1}', 3600));
        $this->assertSame('{"data":1}', $cache->get('k1'));
        $this->assertNull($cache->get('missing'));
        $cache->set('k2', 'x', 0);
        $this->assertSame('d', $cache->get('k2', 'd'));
        $this->assertTrue($cache->delete('k1'));
        $this->assertNull($cache->get('k1'));

        list($c, $t) = Fixtures::client(array('cache' => $cache));
        $c->rates()->list();
        $again = new Client('sat_live_test', array('transport' => $t, 'base_url' => 'https://api.test/v1', 'cache' => new FileCache($dir)));
        $this->assertTrue($again->rates()->list()->cached, 'a second process reads the file');
        $this->assertCount(1, $t->requests);
        $this->assertTrue($cache->clear());
        $this->assertSame(array(), glob($dir . '/satelit-*.json'));
        @rmdir($dir);
    }

    public function testTtl()
    {
        $this->assertSame(0, Ttl::forPath('/me'));
        $this->assertSame(0, Ttl::forPath('/feed'));
        $this->assertSame(0, Ttl::forPath('/export/rates'));
        $this->assertSame(3 * 3600, Ttl::forPath('/rates'));
        $this->assertSame(3600, Ttl::forPath('/fuel'));
        $this->assertSame(600, Ttl::forPath('/news/now'));
        $this->assertSame(86400, Ttl::forPath('/movies/titles/m603'));
        $this->assertSame(86400, Ttl::forPath('/movies/titles/m603/full'));
        $this->assertSame(7200, Ttl::forPath('/movies/titles/m603/showtimes'));
        $this->assertSame(7200, Ttl::forPath('/movies/cinemas/today'));
        $this->assertSame(7 * 86400, Ttl::forPath('/movies/genres'));
    }

    public function testPaginate()
    {
        $t = new MockTransport(function ($url) {
            parse_str(parse_url($url, PHP_URL_QUERY), $q);
            $p = (int) $q['page'];
            return new HttpResponse(200, array(), json_encode(Fixtures::ok(
                array(array('id' => "n_{$p}1"), array('id' => "n_{$p}2")),
                array('page' => array('page' => $p, 'per' => 2, 'total' => 6, 'pages' => 3))
            )));
        });
        $c = new Client('k', array('transport' => $t));
        $ids = array();
        foreach ($c->news()->each(array('per' => 2)) as $card) {
            $ids[] = $card['id'];
        }
        $this->assertSame(array('n_11', 'n_12', 'n_21', 'n_22', 'n_31', 'n_32'), $ids);
        $this->assertCount(3, $t->requests);

        $few = iterator_to_array($c->paginate('/news', array(), 1), false);
        $this->assertCount(2, $few);
    }

    public function testFeedFollowsTheCursor()
    {
        list($c, $t) = Fixtures::queued();
        $t->push(200, Fixtures::ok(array(array('id' => '1', 'type' => 'rates.updated')), array('next' => 'c1', 'has_more' => true)));
        $t->push(200, Fixtures::ok(array(array('id' => '2', 'type' => 'fuel.cap')), array('next' => 'c2', 'has_more' => false)));
        $feed = $c->feed(array('after' => 'c0', 'types' => array('rates.updated', 'fuel.cap')));
        $types = array();
        foreach ($feed as $e) {
            $types[] = $e['type'];
        }
        $this->assertSame(array('rates.updated', 'fuel.cap'), $types);
        $this->assertSame('c2', $feed->getReturn());
        $this->assertStringContainsString('after=c0', $t->requests[0]['url']);
        $this->assertStringContainsString('after=c1', $t->requests[1]['url']);
        $this->assertStringContainsString('types=rates.updated%2Cfuel.cap', $t->requests[0]['url']);
    }

    public function testFeedWithoutPlan()
    {
        list($c, $t) = Fixtures::queued();
        $t->push(402, Fixtures::err('plan_required'));
        $this->expectException(PlanRequiredException::class);
        foreach ($c->feed() as $e) {
        }
    }

    public function testExport()
    {
        list($c, $t) = Fixtures::queued();
        $t->push(200, "{\"_meta\":{\"product\":\"rates\"}}\n{\"code\":\"EUR\",\"value\":20.14}\n\n{\"code\":\"USD\",\"value\":18.37}\n{\"_end\":{\"rows\":2,\"complete\":true}}\n");
        $gen = $c->export('rates');
        $codes = array();
        foreach ($gen as $row) {
            $codes[] = $row['code'];
        }
        $this->assertSame(array('EUR', 'USD'), $codes);
        $end = $gen->getReturn();
        $this->assertSame(2, $end['rows']);
        $this->assertTrue($end['complete']);
        $this->assertSame('rates', $end['meta']['product']);
        $this->assertSame('application/x-ndjson', $t->requests[0]['headers']['Accept']);

        $t->push(200, "{\"_meta\":{}}\n{\"code\":\"EUR\"}\n");
        $this->expectException(ExportIncompleteException::class);
        foreach ($c->export('rates') as $row) {
        }
    }

    public function testExportWithoutPlan()
    {
        list($c, $t) = Fixtures::queued();
        $t->push(402, Fixtures::err('plan_required'));
        $this->expectException(PlanRequiredException::class);
        foreach ($c->export('rates') as $row) {
        }
    }

    public function testResultIsReadOnly()
    {
        list($c) = Fixtures::client();
        $r = $c->rates()->list();
        $this->assertTrue(isset($r[0]));
        $this->assertFalse(isset($r[99]));
        $this->assertNull($r[99]);
        $this->expectException(\LogicException::class);
        $r[0] = 'x';
    }

    public function testRejectsABadTransportOrCache()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client('k', array('cache' => new \stdClass()));
    }
}
