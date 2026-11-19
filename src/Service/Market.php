<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /market: aggregate classifieds market data (no ad, no contact, ever).
 */
final class Market extends AbstractService
{
    /**
     * Categories with data. 2 credits.
     *
     * @return Result
     */
    public function categories(array $params = array())
    {
        return $this->client->get('/market/categories', $params);
    }

    /**
     * Price distributions. 2 credits per 50 rows.
     * Params: category, city, page.
     *
     * @return Result
     */
    public function prices(array $params = array())
    {
        return $this->client->get('/market/prices', $params);
    }

    /**
     * A category's series. 3 credits per started 30 days.
     * Params: category (0 = whole market), city, days (90).
     *
     * @return Result
     */
    public function history(array $params = array())
    {
        return $this->client->get('/market/history', $params);
    }

    /**
     * Recurring sellers, most active first. 3 credits per 50 rows.
     * Params: category, new, page.
     *
     * @return Result
     */
    public function sellers(array $params = array())
    {
        return $this->client->get('/market/sellers', $params);
    }

    /**
     * One seller. 2 credits.
     *
     * @return Result
     */
    public function seller($id, array $params = array())
    {
        return $this->client->get('/market/sellers/' . $this->seg($id), $params);
    }
}
