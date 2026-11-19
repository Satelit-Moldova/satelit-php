<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /catalog: the shop catalogue's category tree.
 */
final class Catalog extends AbstractService
{
    /**
     * The tree in every language. 0 credits.
     *
     * @return Result
     */
    public function tree(array $params = array())
    {
        return $this->client->get('/catalog/tree', $params);
    }
}
