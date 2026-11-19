<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /indexes: a food basket and how shop prices move.
 */
final class Indexes extends AbstractService
{
    /**
     * The basket's cost. 2 credits + 1 per month past the first.
     * Params: months (1).
     *
     * @return Result
     */
    public function basket(array $params = array())
    {
        return $this->client->get('/indexes/basket', $params);
    }

    /**
     * How shop prices moved. 2 credits.
     * Params: days (7..90, 30).
     *
     * @return Result
     */
    public function moves(array $params = array())
    {
        return $this->client->get('/indexes/moves', $params);
    }
}
