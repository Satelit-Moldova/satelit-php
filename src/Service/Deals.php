<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /deals: believable discounts.
 */
final class Deals extends AbstractService
{
    /**
     * One page (60) of deals. 5 credits.
     * Params: category, store, page (up to 5).
     *
     * @return Result
     */
    public function list(array $params = array())
    {
        return $this->client->get('/deals', $params);
    }

    /**
     * Every item of list(), page after page (the API stops at 5 pages).
     *
     * @return \Generator<int,array>
     */
    public function each(array $params = array())
    {
        return $this->client->paginate('/deals', $params, 5);
    }
}
