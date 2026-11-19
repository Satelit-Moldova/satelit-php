<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /tours: package tours of Moldova's agencies.
 */
final class Tours extends AbstractService
{
    /**
     * One page (50) of tours. 3 credits.
     * Params: country, from, to, nights_min, nights_max, board, agency, sort (price, date), page (up to 5).
     *
     * @return Result
     */
    public function list(array $params = array())
    {
        return $this->client->get('/tours', $params);
    }

    /**
     * Every item of list(), page after page (the API stops at 5 pages).
     *
     * @return \Generator<int,array>
     */
    public function each(array $params = array())
    {
        return $this->client->paginate('/tours', $params, 5);
    }

    /**
     * Offers per country. 1 credit.
     *
     * @return Result
     */
    public function countries(array $params = array())
    {
        return $this->client->get('/tours/countries', $params);
    }

    /**
     * One tour with its price history. 2 credits.
     *
     * @return Result
     */
    public function get($id, array $params = array())
    {
        return $this->client->get('/tours/' . $this->seg($id), $params);
    }
}
