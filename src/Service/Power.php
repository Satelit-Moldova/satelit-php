<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /power: Moldova's power exchange.
 */
final class Power extends AbstractService
{
    /**
     * One day by hour. 1 credit.
     * Params: date (YYYY-MM-DD).
     *
     * @return Result
     */
    public function day(array $params = array())
    {
        return $this->client->get('/power', $params);
    }

    /**
     * Past days.
     * Params: days, hours (bool).
     *
     * @return Result
     */
    public function history(array $params = array())
    {
        return $this->client->get('/power/history', $params);
    }

    /**
     * Bilateral contract sessions.
     * Params: days.
     *
     * @return Result
     */
    public function bilateral(array $params = array())
    {
        return $this->client->get('/power/bilateral', $params);
    }
}
