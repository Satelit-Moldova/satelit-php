<?php

namespace Satelit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Satelit\Client;
use Satelit\Exception\NotFoundException;
use Satelit\Exception\RateLimitedException;
use Satelit\Exception\TransportException;
use Satelit\Http\CurlTransport;
use Satelit\Http\StreamTransport;

/**
 * Both real transports against PHP's built-in server on 127.0.0.1: no
 * network leaves the machine.
 */
final class TransportTest extends TestCase
{
    /** @var resource|null */
    private static $proc;
    /** @var string */
    private static $base;

    public static function setUpBeforeClass(): void
    {
        if (!function_exists('proc_open')) {
            return;
        }
        $port = 18000 + (getmypid() % 1000);
        $cmd = escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:' . $port . ' ' . escapeshellarg(__DIR__ . '/../server.php');
        $null = DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';
        self::$proc = proc_open($cmd, array(array('pipe', 'r'), array('file', $null, 'w'), array('file', $null, 'w')), $pipes);
        self::$base = 'http://127.0.0.1:' . $port . '/v1';
        for ($i = 0; $i < 50; $i++) {
            $fp = @fsockopen('127.0.0.1', $port, $no, $err, 0.1);
            if ($fp) {
                fclose($fp);
                return;
            }
            usleep(100000);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$proc) {
            proc_terminate(self::$proc);
            proc_close(self::$proc);
        }
    }

    protected function setUp(): void
    {
        if (!self::$proc) {
            $this->markTestSkipped('proc_open is disabled');
        }
    }

    public function transports()
    {
        $list = array('streams' => array('stream'));
        if (function_exists('curl_init')) {
            $list['curl'] = array('curl');
        }
        return $list;
    }

    private function client($kind, array $opts = array())
    {
        $t = $kind === 'curl' ? new CurlTransport() : new StreamTransport();
        return new Client('sat_live_test', array_merge(array('transport' => $t, 'base_url' => self::$base, 'lang' => 'en'), $opts));
    }

    /**
     * @dataProvider transports
     */
    public function testGetSendsHeadersAndReadsMeta($kind)
    {
        $r = $this->client($kind)->rates()->list();
        $this->assertSame(20.1411, $r[0]['value']);
        $this->assertSame('Bearer sat_live_test', $r[0]['auth']);
        $this->assertSame('en', $r[0]['lang']);
        $this->assertStringStartsWith('satelit-php/', $r[0]['ua']);
        $this->assertSame(299999, $r->rate->creditsRemaining);
        $this->assertSame(600, $r->rate->limit);
        $this->assertSame(200, $r->rate->statusCode);
        $this->assertStringContainsString('Banca Națională', $r->meta->attribution);
    }

    /**
     * @dataProvider transports
     */
    public function testRedirectAndErrors($kind)
    {
        $c = $this->client($kind, array('retries' => 1, 'sleep' => function () {
        }));
        $this->assertSame('EUR', $c->get('/redirect')->data[0]['code']);
        try {
            $c->get('/missing');
            $this->fail('no exception');
        } catch (NotFoundException $e) {
            $this->assertSame('not_found', $e->getErrorCode());
            $this->assertSame('Nu am găsit.', $e->getApiMessage());
        }
        try {
            $c->get('/busy');
            $this->fail('no exception');
        } catch (RateLimitedException $e) {
            $this->assertSame(1, $e->getRetryAfter());
        }
    }

    /**
     * @dataProvider transports
     */
    public function testExportStreams($kind)
    {
        $gen = $this->client($kind)->export('rates');
        $n = 0;
        foreach ($gen as $row) {
            $this->assertSame($n, $row['i']);
            $n++;
        }
        $this->assertSame(5000, $n);
        $end = $gen->getReturn();
        $this->assertTrue($end['complete']);
    }

    /**
     * @dataProvider transports
     */
    public function testTimeout($kind)
    {
        $c = $this->client($kind, array('timeout' => 1));
        $start = microtime(true);
        try {
            $c->get('/slow');
            $this->fail('no exception');
        } catch (TransportException $e) {
            $this->assertLessThan(2.9, microtime(true) - $start);
        }
    }

    public function testNothingListens()
    {
        $c = new Client('k', array('transport' => new StreamTransport(), 'base_url' => 'http://127.0.0.1:1/v1', 'timeout' => 2));
        $this->expectException(TransportException::class);
        $c->rates()->list();
    }
}
