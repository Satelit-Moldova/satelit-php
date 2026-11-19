<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /geo: addresses to points (OpenStreetMap, ODbL).
 */
final class Geo extends AbstractService
{
    /**
     * An address to one point; data null when the town cannot be read. 1 credit.
     * Params: city.
     *
     * @return Result
     */
    public function search($q, array $params = array())
    {
        $params['q'] = $q;
        return $this->client->get('/geo/search', $params);
    }

    /**
     * Streets by name.
     * Params: near, radius, page.
     *
     * @return Result
     */
    public function streets($q, array $params = array())
    {
        $params['q'] = $q;
        return $this->client->get('/geo/streets', $params);
    }
}
