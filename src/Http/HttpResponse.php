<?php

namespace Satelit\Http;

/**
 * One HTTP answer: status, headers (names in lower case) and either the body
 * as a string or, for an export, an open stream positioned at its start.
 */
final class HttpResponse
{
    /** @var int */
    public $status;
    /** @var array<string,string> */
    public $headers;
    /** @var string */
    public $body;
    /** @var resource|null */
    public $stream;

    /**
     * @param int $status
     * @param array<string,string> $headers
     * @param string $body
     * @param resource|null $stream
     */
    public function __construct($status, array $headers, $body = '', $stream = null)
    {
        $this->status = (int) $status;
        $lower = array();
        foreach ($headers as $name => $value) {
            $lower[strtolower($name)] = (string) $value;
        }
        $this->headers = $lower;
        $this->body = (string) $body;
        $this->stream = $stream;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function header($name)
    {
        $name = strtolower($name);
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    /**
     * The body, read from the stream when there is one.
     *
     * @return string
     */
    public function contents()
    {
        if ($this->stream !== null && $this->body === '') {
            $this->body = (string) stream_get_contents($this->stream);
        }
        return $this->body;
    }

    /**
     * Parses raw header lines ("HTTP/1.1 200 OK", "Name: value") into a status
     * and headers. With redirects several blocks come in: the last one wins.
     *
     * @param string[] $lines
     * @return array{0:int,1:array<string,string>}
     */
    public static function parseHeaderLines(array $lines)
    {
        $status = 0;
        $headers = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $status = (int) $m[1];
                $headers = array();
                continue;
            }
            $pos = strpos($line, ':');
            if ($pos !== false) {
                $headers[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos + 1));
            }
        }
        return array($status, $headers);
    }
}
