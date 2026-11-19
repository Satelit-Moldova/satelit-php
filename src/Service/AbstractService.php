<?php

namespace Satelit\Service;

use Satelit\Client;

/**
 * Base of the product groups: each method is one GET route of the API.
 */
abstract class AbstractService
{
    /** @var Client */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Escapes one path segment (an id, a slug, a date).
     *
     * @param string|int $value
     * @return string
     */
    protected function seg($value)
    {
        $value = (string) $value;
        if ($value === '') {
            throw new \InvalidArgumentException('An id or slug in the path is empty.');
        }
        return rawurlencode($value);
    }
}
