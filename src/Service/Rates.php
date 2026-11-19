<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /rates: BNM's official exchange rates (CC BY 4.0: show meta->attribution).
 */
final class Rates extends AbstractService
{
    /**
     * Today's official rates. 1 credit.
     *
     * @return Result
     */
    public function list(array $params = array())
    {
        return $this->client->get('/rates', $params);
    }

    /**
     * One currency's rates over days. 1 credit per started 30 days.
     * Params: days (30).
     *
     * @return Result
     */
    public function history($code, array $params = array())
    {
        $params['code'] = $code;
        return $this->client->get('/rates/history', $params);
    }
}
