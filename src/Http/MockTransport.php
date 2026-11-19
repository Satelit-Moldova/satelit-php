<?php

namespace Satelit\Http;

/**
 * A transport that answers from a queue or a callback, for tests (yours and
 * ours). Every request is recorded in $requests.
 *
 *     $mock = new MockTransport();
 *     $mock->push(200, ['data' => [...], 'meta' => [...]]);
 *     $client = new Client('key', ['transport' => $mock]);
 */
final class MockTransport implements TransportInterface
{
    /** @var array<int,array{url:string,headers:array,timeout:float}> */
    public $requests = array();

    /** @var HttpResponse[] */
    private $queue = array();

    /** @var callable|null */
    private $handler;

    /**
     * @param callable|null $handler function (string $url, array $headers): HttpResponse
     */
    public function __construct($handler = null)
    {
        $this->handler = $handler;
    }

    /**
     * Queues an answer. $body may be an array (encoded as JSON) or a string.
     *
     * @param int $status
     * @param array|string $body
     * @param array<string,string> $headers
     * @return $this
     */
    public function push($status, $body, array $headers = array())
    {
        $this->queue[] = new HttpResponse($status, $headers, is_string($body) ? $body : json_encode($body));
        return $this;
    }

    public function get($url, array $headers, $timeout)
    {
        $this->requests[] = array('url' => $url, 'headers' => $headers, 'timeout' => $timeout);
        if ($this->handler !== null) {
            return call_user_func($this->handler, $url, $headers);
        }
        if (!$this->queue) {
            throw new \LogicException('MockTransport: no answer queued for ' . $url);
        }
        return array_shift($this->queue);
    }

    public function stream($url, array $headers, $timeout)
    {
        $res = $this->get($url, $headers, $timeout);
        $fp = fopen('php://memory', 'w+b');
        fwrite($fp, $res->body);
        rewind($fp);
        return new HttpResponse($res->status, $res->headers, '', $fp);
    }
}
