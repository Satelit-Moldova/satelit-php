<?php

namespace Satelit\Http;

use Satelit\Exception\TransportException;

/**
 * Transport over PHP's own HTTP streams, for hosting without curl.
 * Needs allow_url_fopen and the openssl extension.
 */
final class StreamTransport implements TransportInterface
{
    public function __construct()
    {
        if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
            throw new TransportException('allow_url_fopen is off and curl is missing: no way to reach the API.');
        }
    }

    public function get($url, array $headers, $timeout)
    {
        $fp = $this->open($url, $headers, $timeout);
        $body = stream_get_contents($fp);
        $meta = stream_get_meta_data($fp);
        fclose($fp);
        if ($body === false || !empty($meta['timed_out'])) {
            throw new TransportException('GET timed out or failed while reading.');
        }
        $parsed = $this->parse($meta);
        return new HttpResponse($parsed[0], $parsed[1], $body);
    }

    public function stream($url, array $headers, $timeout)
    {
        $fp = $this->open($url, $headers, $timeout);
        $meta = stream_get_meta_data($fp);
        // From now on a read that waits 30 s is a stall.
        stream_set_timeout($fp, 30);
        $parsed = $this->parse($meta);
        return new HttpResponse($parsed[0], $parsed[1], '', $fp);
    }

    /**
     * @return resource
     */
    private function open($url, array $headers, $timeout)
    {
        // HTTP/1.1 with "Connection: close": PHP's wrapper decodes chunked
        // bodies itself, and the read ends when the server closes.
        $lines = "Connection: close\r\n";
        foreach ($headers as $name => $value) {
            $lines .= $name . ': ' . $value . "\r\n";
        }
        $ctx = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => $lines,
                'timeout' => (float) $timeout,
                'ignore_errors' => true, // give us 4xx/5xx bodies instead of a warning
                'follow_location' => 1,
                'max_redirects' => 3,
                'protocol_version' => 1.1,
            ),
            'ssl' => array('verify_peer' => true, 'verify_peer_name' => true),
        ));
        $err = null;
        set_error_handler(function ($no, $str) use (&$err) {
            $err = $str;
            return true;
        });
        try {
            $fp = fopen($url, 'rb', false, $ctx);
        } finally {
            restore_error_handler();
        }
        if ($fp === false) {
            throw new TransportException('GET failed: ' . ($err ?: 'no answer'));
        }
        return $fp;
    }

    /**
     * @return array{0:int,1:array<string,string>}
     */
    private function parse(array $meta)
    {
        $lines = isset($meta['wrapper_data']) && is_array($meta['wrapper_data']) ? $meta['wrapper_data'] : array();
        return HttpResponse::parseHeaderLines($lines);
    }
}
