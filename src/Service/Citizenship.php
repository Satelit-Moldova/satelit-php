<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /citizenship: Romanian citizenship as ANC publishes it. Show as_of.line with any number.
 */
final class Citizenship extends AbstractService
{
    /**
     * A dossier (12345/RD/2022) or an order. 1 credit.
     *
     * @return Result
     */
    public function lookup($q, array $params = array())
    {
        $params['q'] = $q;
        return $this->client->get('/citizenship/lookup', $params);
    }

    /**
     * Backlog and waits. 2 credits.
     * Params: article, month (YYYY-MM).
     *
     * @return Result
     */
    public function stats(array $params = array())
    {
        return $this->client->get('/citizenship/stats', $params);
    }

    /**
     * Every status list. 1 credit.
     *
     * @return Result
     */
    public function years(array $params = array())
    {
        return $this->client->get('/citizenship/years', $params);
    }

    /**
     * One list (11-2022). 2 credits per 200 rows.
     * Params: after, limit.
     *
     * @return Result
     */
    public function year($slug, array $params = array())
    {
        return $this->client->get('/citizenship/years/' . $this->seg($slug), $params);
    }

    /**
     * Orders, newest first. 2 credits per 50 rows.
     * Params: article, from, to, before, limit.
     *
     * @return Result
     */
    public function orders(array $params = array())
    {
        return $this->client->get('/citizenship/orders', $params);
    }

    /**
     * One order (1054-p-2025). 2 credits per 200 rows.
     * Params: after, limit.
     *
     * @return Result
     */
    public function order($slug, array $params = array())
    {
        return $this->client->get('/citizenship/orders/' . $this->seg($slug), $params);
    }

    /**
     * The next and last oath days. 1 credit.
     *
     * @return Result
     */
    public function oath(array $params = array())
    {
        return $this->client->get('/citizenship/oath', $params);
    }
}
