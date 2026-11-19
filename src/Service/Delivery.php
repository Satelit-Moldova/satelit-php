<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /delivery: the shops' delivery and pickup terms by town.
 */
final class Delivery extends AbstractService
{
    /**
     * The terms, for one town with city. 2 credits.
     * Params: city.
     *
     * @return Result
     */
    public function get(array $params = array())
    {
        return $this->client->get('/delivery', $params);
    }
}
