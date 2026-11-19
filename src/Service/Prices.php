<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /prices: shop prices read from the shops' public pages. Show the shop's name with each offer.
 */
final class Prices extends AbstractService
{
    /**
     * The open shops. 1 credit.
     *
     * @return Result
     */
    public function stores(array $params = array())
    {
        return $this->client->get('/prices/stores', $params);
    }

    /**
     * Products by words. 3 credits per 20 rows.
     * Params: category, store, in_stock, page (up to 5).
     *
     * @return Result
     */
    public function search($q, array $params = array())
    {
        $params['q'] = $q;
        return $this->client->get('/prices/search', $params);
    }

    /**
     * Every item of search(), page after page (the API stops at 5 pages).
     *
     * @return \Generator<int,array>
     */
    public function eachSearch($q, array $params = array())
    {
        $params['q'] = $q;
        return $this->client->paginate('/prices/search', $params, 5);
    }

    /**
     * The catalogue's products (milk, bread). 2 credits.
     * Params: category.
     *
     * @return Result
     */
    public function groups(array $params = array())
    {
        return $this->client->get('/prices/groups', $params);
    }

    /**
     * One group with up to 50 offers. 3 credits.
     *
     * @return Result
     */
    public function group($id, array $params = array())
    {
        return $this->client->get('/prices/groups/' . $this->seg($id), $params);
    }

    /**
     * One product with its offers. 2 credits.
     *
     * @return Result
     */
    public function item($id, array $params = array())
    {
        return $this->client->get('/prices/items/' . $this->seg($id), $params);
    }

    /**
     * A product's prices over time. 5 credits per started 30 days.
     * Params: days (30), store.
     *
     * @return Result
     */
    public function itemHistory($id, array $params = array())
    {
        return $this->client->get('/prices/items/' . $this->seg($id) . '/history', $params);
    }
}
