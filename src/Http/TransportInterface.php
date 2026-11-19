<?php

namespace Satelit\Http;

use Satelit\Exception\TransportException;

/**
 * Sends one GET. Implementations must not throw on an HTTP error status:
 * they return it, and the client turns it into an ApiException.
 */
interface TransportInterface
{
    /**
     * @param string $url
     * @param array<string,string> $headers
     * @param float $timeout seconds for the whole request
     * @return HttpResponse with $body set
     * @throws TransportException when no answer came (DNS, TLS, timeout)
     */
    public function get($url, array $headers, $timeout);

    /**
     * Like get(), for long answers (an export): connecting and waiting for the
     * headers are bounded by $timeout, the body may take longer.
     *
     * @param string $url
     * @param array<string,string> $headers
     * @param float $timeout
     * @return HttpResponse with $stream set (read it, then fclose)
     * @throws TransportException
     */
    public function stream($url, array $headers, $timeout);
}
