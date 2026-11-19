<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /law: the Official Gazette's acts and daily digests.
 */
final class Law extends AbstractService
{
    /**
     * One page (20) of acts, newest first. 2 credits, 3 with q.
     * Params: q, type, topic, issuer, important, from, to, page.
     *
     * @return Result
     */
    public function acts(array $params = array())
    {
        return $this->client->get('/law/acts', $params);
    }

    /**
     * Every item of acts(), page after page.
     *
     * @return \Generator<int,array>
     */
    public function eachActs(array $params = array())
    {
        return $this->client->paginate('/law/acts', $params, 0);
    }

    /**
     * One act. 1 credit.
     *
     * @return Result
     */
    public function act($id, array $params = array())
    {
        return $this->client->get('/law/acts/' . $this->seg($id), $params);
    }

    /**
     * The daily digests. 2 credits.
     * Params: days (7).
     *
     * @return Result
     */
    public function digests(array $params = array())
    {
        return $this->client->get('/law/digests', $params);
    }

    /**
     * One day's digest (YYYY-MM-DD). 2 credits.
     *
     * @return Result
     */
    public function digest($date, array $params = array())
    {
        return $this->client->get('/law/digests/' . $this->seg($date), $params);
    }
}
