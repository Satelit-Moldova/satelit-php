<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /tariffs: regulated utility tariffs.
 */
final class Tariffs extends AbstractService
{
    /**
     * The tariffs in force. 1 credit.
     *
     * @return Result
     */
    public function list(array $params = array())
    {
        return $this->client->get('/tariffs', $params);
    }
}
