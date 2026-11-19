<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /insurance: BNM's insurance statistics and reference tariffs.
 */
final class Insurance extends AbstractService
{
    /**
     * The market by class and quarter. 2 credits.
     *
     * @return Result
     */
    public function market(array $params = array())
    {
        return $this->client->get('/insurance/market', $params);
    }

    /**
     * Every insurer's figures. 2 credits.
     *
     * @return Result
     */
    public function insurers(array $params = array())
    {
        return $this->client->get('/insurance/insurers', $params);
    }

    /**
     * Reference tariffs.
     * Params: on, category, zone, holder, k4, bm, months.
     *
     * @return Result
     */
    public function tariffs(array $params = array())
    {
        return $this->client->get('/insurance/tariffs', $params);
    }
}
