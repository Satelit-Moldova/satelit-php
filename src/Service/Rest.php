<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /rest: places to rest in Moldova.
 */
final class Rest extends AbstractService
{
    /**
     * One page (50) of places. 3 credits.
     * Params: region, kind, page (up to 5).
     *
     * @return Result
     */
    public function list(array $params = array())
    {
        return $this->client->get('/rest', $params);
    }

    /**
     * Every item of list(), page after page (the API stops at 5 pages).
     *
     * @return \Generator<int,array>
     */
    public function each(array $params = array())
    {
        return $this->client->paginate('/rest', $params, 5);
    }
}
