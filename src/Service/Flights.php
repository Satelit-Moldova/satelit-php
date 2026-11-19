<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /flights: flights from Chișinău.
 */
final class Flights extends AbstractService
{
    /**
     * The cities flown to. 2 credits.
     *
     * @return Result
     */
    public function destinations(array $params = array())
    {
        return $this->client->get('/flights/destinations', $params);
    }

    /**
     * One destination by IATA code. 3 credits.
     *
     * @return Result
     */
    public function destination($code, array $params = array())
    {
        return $this->client->get('/flights/destinations/' . $this->seg($code), $params);
    }

    /**
     * The airport's board of a day. 2 credits.
     * Params: kind (departures, arrivals), day (YYYY-MM-DD).
     *
     * @return Result
     */
    public function board(array $params = array())
    {
        return $this->client->get('/flights/board', $params);
    }
}
