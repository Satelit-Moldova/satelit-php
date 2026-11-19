<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /places: shops and venues from OpenStreetMap (ODbL: show meta->attribution).
 */
final class Places extends AbstractService
{
    /**
     * One page (100) of places. 2 credits.
     * Params: kind (shop, venue), city, chain, category, near (lat,lon), radius, source (chains), page.
     *
     * @return Result
     */
    public function list(array $params = array())
    {
        return $this->client->get('/places', $params);
    }

    /**
     * Every item of list(), page after page.
     *
     * @return \Generator<int,array>
     */
    public function each(array $params = array())
    {
        return $this->client->paginate('/places', $params, 0);
    }
}
