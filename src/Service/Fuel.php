<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /fuel: fuel prices of every network, ANRE's cap, stations.
 */
final class Fuel extends AbstractService
{
    /**
     * Today's prices of every network. 1 credit.
     *
     * @return Result
     */
    public function board(array $params = array())
    {
        return $this->client->get('/fuel', $params);
    }

    /**
     * One page (100) of stations. 5 credits a page.
     * Params: network, raion, page.
     *
     * @return Result
     */
    public function stations(array $params = array())
    {
        return $this->client->get('/fuel/stations', $params);
    }

    /**
     * Every item of stations(), page after page.
     *
     * @return \Generator<int,array>
     */
    public function eachStations(array $params = array())
    {
        return $this->client->paginate('/fuel/stations', $params, 0);
    }

    /**
     * One fuel's average over time (a95, diesel, lpg). 2 credits per started 30 days.
     * Params: network, days (30).
     *
     * @return Result
     */
    public function history($fuel, array $params = array())
    {
        $params['fuel'] = $fuel;
        return $this->client->get('/fuel/history', $params);
    }
}
