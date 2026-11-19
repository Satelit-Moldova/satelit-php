<?php

namespace Satelit\Tests;

use Satelit\Client;
use Satelit\Http\HttpResponse;
use Satelit\Http\MockTransport;

/**
 * Recorded live answers (tests/fixtures/samples.json) served by a MockTransport.
 */
final class Fixtures
{
    /** @var array|null */
    private static $samples;

    public static function samples()
    {
        if (self::$samples === null) {
            self::$samples = json_decode(file_get_contents(__DIR__ . '/fixtures/samples.json'), true);
        }
        return self::$samples;
    }

    /**
     * A transport answering each path with its recorded sample (query ignored).
     *
     * @return MockTransport
     */
    public static function transport()
    {
        $byPath = array();
        foreach (self::samples() as $route => $s) {
            $p = explode('?', $route, 2);
            if (!isset($byPath[$p[0]])) {
                $byPath[$p[0]] = $s;
            }
        }
        return new MockTransport(function ($url) use ($byPath) {
            $path = substr(parse_url($url, PHP_URL_PATH), strlen('/v1'));
            if (!isset($byPath[$path])) {
                return new HttpResponse(404, array(), '{"error":{"code":"not_found","message":"Nu am găsit.","docs":"https://developer.satelit.md/en/errors/#not_found"}}');
            }
            $s = $byPath[$path];
            return new HttpResponse($s['status'], $s['headers'], json_encode($s['body']));
        });
    }

    /**
     * @return array{0:Client,1:MockTransport}
     */
    public static function client(array $options = array())
    {
        $t = self::transport();
        $options = array_merge(array(
            'transport' => $t,
            'base_url' => 'https://api.test/v1',
            'sleep' => function () {
            },
        ), $options);
        return array(new Client('sat_live_test', $options), $t);
    }

    /**
     * @return array{0:Client,1:MockTransport}
     */
    public static function queued(array $options = array())
    {
        $t = new MockTransport();
        $options = array_merge(array(
            'transport' => $t,
            'base_url' => 'https://api.test/v1',
            'sleep' => function () {
            },
        ), $options);
        return array(new Client('sat_live_test', $options), $t);
    }

    public static function ok($data, array $meta = array())
    {
        return array('data' => $data, 'meta' => array_merge(array(
            'credits' => 1, 'updated_at' => null, 'sources' => array(), 'attribution' => null,
        ), $meta));
    }

    public static function err($code, $message = 'm', array $extra = array())
    {
        return array('error' => array_merge(array(
            'code' => $code, 'message' => $message, 'docs' => 'https://developer.satelit.md/en/errors/#' . $code,
        ), $extra));
    }
}
