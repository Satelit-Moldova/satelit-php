<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /companies: the state register and what goes with it.
 */
final class Companies extends AbstractService
{
    /**
     * Companies by name or IDNO start. 3 credits per 20 rows.
     * Params: city, form, active, sector, klass, sort, page (up to 5), per.
     *
     * @return Result
     */
    public function search($q, array $params = array())
    {
        $params['q'] = $q;
        return $this->client->get('/companies', $params);
    }

    /**
     * Every item of search(), page after page (the API stops at 5 pages).
     *
     * @return \Generator<int,array>
     */
    public function eachSearch($q, array $params = array())
    {
        $params['q'] = $q;
        return $this->client->paginate('/companies', $params, 5);
    }

    /**
     * Counts of the register. 2 credits.
     *
     * @return Result
     */
    public function stats(array $params = array())
    {
        return $this->client->get('/companies/stats', $params);
    }

    /**
     * One company by IDNO or slug. 2 credits.
     *
     * @return Result
     */
    public function get($idno, array $params = array())
    {
        return $this->client->get('/companies/' . $this->seg($idno), $params);
    }

    /**
     * A company's procurement awards. 2 credits per 50 rows.
     * Params: page.
     *
     * @return Result
     */
    public function contracts($idno, array $params = array())
    {
        return $this->client->get('/companies/' . $this->seg($idno) . '/contracts', $params);
    }

    /**
     * Licensed non-bank lenders.
     * Params: active, q, page.
     *
     * @return Result
     */
    public function licences(array $params = array())
    {
        return $this->client->get('/companies/licences', $params);
    }

    /**
     * One company's licences.
     *
     * @return Result
     */
    public function companyLicences($idno, array $params = array())
    {
        return $this->client->get('/companies/' . $this->seg($idno) . '/licences', $params);
    }

    /**
     * ASP's legal notices.
     * Params: kind, from, to, page.
     *
     * @return Result
     */
    public function notices(array $params = array())
    {
        return $this->client->get('/companies/notices', $params);
    }

    /**
     * One company's notices.
     *
     * @return Result
     */
    public function companyNotices($idno, array $params = array())
    {
        return $this->client->get('/companies/' . $this->seg($idno) . '/notices', $params);
    }
}
