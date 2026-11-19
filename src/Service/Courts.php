<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /courts: court hearings as the courts publish them.
 */
final class Courts extends AbstractService
{
    /**
     * Every court. 1 credit.
     *
     * @return Result
     */
    public function courts(array $params = array())
    {
        return $this->client->get('/courts/courts', $params);
    }

    /**
     * Cases by a name or a number. 3 credits per 20 rows.
     * Params: court, kind, per.
     *
     * @return Result
     */
    public function search($q, array $params = array())
    {
        $params['q'] = $q;
        return $this->client->get('/courts/search', $params);
    }

    /**
     * A day's hearings; meta->next for more. 2 credits per 50 rows.
     * Params: court, date (today, tomorrow, YYYY-MM-DD), kind, q, after, per.
     *
     * @return Result
     */
    public function hearings(array $params = array())
    {
        return $this->client->get('/courts/hearings', $params);
    }

    /**
     * One case by number, PIGD or page link. 1 credit.
     * Params: court.
     *
     * @return Result
     */
    public function caseByNumber($number, array $params = array())
    {
        $params['number'] = $number;
        return $this->client->get('/courts/case', $params);
    }

    /**
     * A person's or company's page. 3 credits per 20 rows.
     * Params: court, side, year, status, type, page, per.
     *
     * @return Result
     */
    public function name($kind, $slug, array $params = array())
    {
        return $this->client->get('/courts/names/' . $this->seg($kind) . '/' . $this->seg($slug), $params);
    }

    /**
     * A judge's load. 1 credit.
     *
     * @return Result
     */
    public function judge($slug, array $params = array())
    {
        return $this->client->get('/courts/judges/' . $this->seg($slug), $params);
    }

    /**
     * The courts' figures. 2 credits.
     * Params: view (now, trends, judges, durations), court, kind, period, days, limit.
     *
     * @return Result
     */
    public function stats(array $params = array())
    {
        return $this->client->get('/courts/stats', $params);
    }
}
