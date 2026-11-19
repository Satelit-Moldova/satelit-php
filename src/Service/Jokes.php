<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /jokes: jokes, riddles, proverbs, rated for Moldova.
 */
final class Jokes extends AbstractService
{
    /**
     * Jokes, funniest first. 1 credit per 20 rows, 2 with q.
     * Params: q, topic, kind, audience, max_age, min_score, exclude_flags, page, per.
     *
     * @return Result
     */
    public function list(array $params = array())
    {
        return $this->client->get('/jokes', $params);
    }

    /**
     * The day's joke. 1 credit.
     * Params: topic.
     *
     * @return Result
     */
    public function today(array $params = array())
    {
        return $this->client->get('/jokes/today', $params);
    }

    /**
     * One joke not seen yet; NotFoundException when all were. 1 credit.
     * Params: filters of list(), seen (ids).
     *
     * @return Result
     */
    public function random(array $params = array())
    {
        return $this->client->get('/jokes/random', $params);
    }

    /**
     * The topics. 1 credit.
     * Params: audience.
     *
     * @return Result
     */
    public function topics(array $params = array())
    {
        return $this->client->get('/jokes/topics', $params);
    }

    /**
     * One joke in every language where it works. 1 credit.
     *
     * @return Result
     */
    public function get($id, array $params = array())
    {
        return $this->client->get('/jokes/' . $this->seg($id), $params);
    }
}
