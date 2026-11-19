<?php

namespace Satelit\Http;

use Satelit\Exception\TransportException;

/**
 * Transport over the curl extension (the default when it is loaded).
 */
final class CurlTransport implements TransportInterface
{
    public function __construct()
    {
        if (!function_exists('curl_init')) {
            throw new TransportException('The curl extension is not loaded; use StreamTransport.');
        }
    }

    public function get($url, array $headers, $timeout)
    {
        $ch = $this->handle($url, $headers, $timeout);
        $lines = array();
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, (int) round($timeout * 1000));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $line) use (&$lines) {
            $lines[] = $line;
            return strlen($line);
        });
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            $no = curl_errno($ch);
            $this->close($ch);
            throw new TransportException('GET failed: ' . $err, $no);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $this->close($ch);
        $parsed = HttpResponse::parseHeaderLines($lines);
        return new HttpResponse($status ?: $parsed[0], $parsed[1], (string) $body);
    }

    public function stream($url, array $headers, $timeout)
    {
        // The body goes to php://temp (memory, then a temp file past 2 MB),
        // so a large export never sits whole in memory.
        $file = fopen('php://temp/maxmemory:2097152', 'w+b');
        if ($file === false) {
            throw new TransportException('Cannot open a temporary stream.');
        }
        $ch = $this->handle($url, $headers, $timeout);
        $lines = array();
        curl_setopt($ch, CURLOPT_FILE, $file);
        // No total timeout: the stream may run for minutes. A stall of 30 s ends it.
        curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1);
        curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 30);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $line) use (&$lines) {
            $lines[] = $line;
            return strlen($line);
        });
        $ok = curl_exec($ch);
        if ($ok === false) {
            $err = curl_error($ch);
            $no = curl_errno($ch);
            $this->close($ch);
            fclose($file);
            throw new TransportException('GET failed: ' . $err, $no);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $this->close($ch);
        rewind($file);
        $parsed = HttpResponse::parseHeaderLines($lines);
        return new HttpResponse($status ?: $parsed[0], $parsed[1], '', $file);
    }

    /**
     * @return resource|\CurlHandle
     */
    private function handle($url, array $headers, $timeout)
    {
        $ch = curl_init($url);
        $list = array();
        foreach ($headers as $name => $value) {
            $list[] = $name . ': ' . $value;
        }
        curl_setopt_array($ch, array(
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $list,
            CURLOPT_CONNECTTIMEOUT_MS => (int) round(min($timeout, 10) * 1000),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING => '',
        ));
        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
        }
        return $ch;
    }

    private function close($ch)
    {
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }
        // PHP 8 frees the handle when it goes out of scope (curl_close is deprecated in 8.5).
    }
}
