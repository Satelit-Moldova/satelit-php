<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /news: Moldova's news as Satelit's newsroom writes it, events and reactions. Cache 10-15 minutes.
 */
final class News extends AbstractService
{
    /**
     * One page of stories, newest first. 2 credits.
     * Params: kind, category, region, slot, min_gravity, from, to, page, per (up to 20).
     *
     * @return Result
     */
    public function list(array $params = array())
    {
        return $this->client->get('/news', $params);
    }

    /**
     * Every item of list(), page after page.
     *
     * @return \Generator<int,array>
     */
    public function each(array $params = array())
    {
        return $this->client->paginate('/news', $params, 0);
    }

    /**
     * What to show one reader now; meta->raw['slot'], ['region']. 2 credits.
     * Params: region, seen (ids), hours (24), per (10).
     *
     * @return Result
     */
    public function now(array $params = array())
    {
        return $this->client->get('/news/now', $params);
    }

    /**
     * Stories by words (2..100 characters). 3 credits.
     * Params: page.
     *
     * @return Result
     */
    public function search($q, array $params = array())
    {
        $params['q'] = $q;
        return $this->client->get('/news/search', $params);
    }

    /**
     * Every story of a day. 10 credits.
     * Params: min_gravity.
     *
     * @return Result
     */
    public function day($date, array $params = array())
    {
        return $this->client->get('/news/days/' . $this->seg($date), $params);
    }

    /**
     * One story in full. 1 credit.
     *
     * @return Result
     */
    public function story($id, array $params = array())
    {
        return $this->client->get('/news/' . $this->seg($id), $params);
    }

    /**
     * The categories. 1 credit.
     *
     * @return Result
     */
    public function categories(array $params = array())
    {
        return $this->client->get('/news/categories', $params);
    }

    /**
     * The regions and times of day. 1 credit.
     *
     * @return Result
     */
    public function regions(array $params = array())
    {
        return $this->client->get('/news/regions', $params);
    }

    /**
     * One page of news events. 2 credits.
     * Params: category, region, slot, min_gravity, social_only, sentiment, from, to, all, page, per.
     *
     * @return Result
     */
    public function events(array $params = array())
    {
        return $this->client->get('/news/events', $params);
    }

    /**
     * Every item of events(), page after page.
     *
     * @return \Generator<int,array>
     */
    public function eachEvents(array $params = array())
    {
        return $this->client->paginate('/news/events', $params, 0);
    }

    /**
     * One event with its sources. 1 credit.
     *
     * @return Result
     */
    public function event($id, array $params = array())
    {
        return $this->client->get('/news/events/' . $this->seg($id), $params);
    }

    /**
     * Comments under an event (plans with social news). 3 credits.
     * Params: sort, platform, min_likes, no_toxic, page, per.
     *
     * @return Result
     */
    public function eventComments($id, array $params = array())
    {
        return $this->client->get('/news/events/' . $this->seg($id) . '/comments', $params);
    }

    /**
     * The outlets read. 1 credit.
     *
     * @return Result
     */
    public function outlets(array $params = array())
    {
        return $this->client->get('/news/outlets', $params);
    }

    /**
     * The reaction to a story's event. 1 credit.
     *
     * @return Result
     */
    public function storyReaction($id, array $params = array())
    {
        return $this->client->get('/news/' . $this->seg($id) . '/reaction', $params);
    }

    /**
     * The comments of a story's event. 3 credits.
     * Params: sort, platform, min_likes, no_toxic, page, per.
     *
     * @return Result
     */
    public function storyComments($id, array $params = array())
    {
        return $this->client->get('/news/' . $this->seg($id) . '/comments', $params);
    }
}
