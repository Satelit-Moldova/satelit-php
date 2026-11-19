<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /estate: median flat prices.
 */
final class Estate extends AbstractService
{
    /**
     * The sale and rent series. 3 credits.
     * Params: days (30).
     *
     * @return Result
     */
    public function series(array $params = array())
    {
        return $this->client->get('/estate', $params);
    }
}
