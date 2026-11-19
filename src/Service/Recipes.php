<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /recipes: recipes with what they cost today.
 */
final class Recipes extends AbstractService
{
    /**
     * Every recipe. 2 credits.
     *
     * @return Result
     */
    public function list(array $params = array())
    {
        return $this->client->get('/recipes', $params);
    }

    /**
     * One recipe with ingredients and steps. 1 credit.
     *
     * @return Result
     */
    public function get($slug, array $params = array())
    {
        return $this->client->get('/recipes/' . $this->seg($slug), $params);
    }
}
