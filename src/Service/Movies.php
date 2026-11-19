<?php

namespace Satelit\Service;

use Satelit\Result;

/**
 * /movies: films, series, people and Moldova's cinemas. Every call costs 40 credits (full 55, genres and board 0): give the client a cache.
 */
final class Movies extends AbstractService
{
    /**
     * Films, series and people.
     * Params: kind (all, movie, series, person), year, page.
     *
     * @return Result
     */
    public function search($q, array $params = array())
    {
        $params['q'] = $q;
        return $this->client->get('/movies/search', $params);
    }

    /**
     * One film (m...) or series (s...).
     *
     * @return Result
     */
    public function title($id, array $params = array())
    {
        return $this->client->get('/movies/titles/' . $this->seg($id), $params);
    }

    /**
     * A title in full. 55 credits.
     *
     * @return Result
     */
    public function titleFull($id, array $params = array())
    {
        return $this->client->get('/movies/titles/' . $this->seg($id) . '/full', $params);
    }

    /**
     * A film's sessions.
     * Params: country (MD).
     *
     * @return Result
     */
    public function showtimes($id, array $params = array())
    {
        return $this->client->get('/movies/titles/' . $this->seg($id) . '/showtimes', $params);
    }

    /**
     * One person (p...).
     *
     * @return Result
     */
    public function person($id, array $params = array())
    {
        return $this->client->get('/movies/people/' . $this->seg($id), $params);
    }

    /**
     * Films or series by filters.
     * Params: kind, genre, year, year_from, year_to, country, language, sort (popular, rating, newest, votes), min_votes, page.
     *
     * @return Result
     */
    public function discover(array $params = array())
    {
        return $this->client->get('/movies/discover', $params);
    }

    /**
     * What is trending.
     * Params: kind, window (day, week), page.
     *
     * @return Result
     */
    public function trending(array $params = array())
    {
        return $this->client->get('/movies/trending', $params);
    }

    /**
     * In cinemas now or soon.
     * Params: country, when (now, next), page.
     *
     * @return Result
     */
    public function inCinemas(array $params = array())
    {
        return $this->client->get('/movies/cinema', $params);
    }

    /**
     * The genres. 0 credits.
     * Params: kind.
     *
     * @return Result
     */
    public function genres(array $params = array())
    {
        return $this->client->get('/movies/genres', $params);
    }

    /**
     * A country's cinemas.
     * Params: country, city.
     *
     * @return Result
     */
    public function cinemas(array $params = array())
    {
        return $this->client->get('/movies/cinemas', $params);
    }

    /**
     * One cinema's sessions of a day.
     * Params: date (YYYY-MM-DD).
     *
     * @return Result
     */
    public function schedule($id, array $params = array())
    {
        return $this->client->get('/movies/cinemas/' . $this->seg($id) . '/schedule', $params);
    }

    /**
     * A day in every cinema.
     * Params: country, city, date.
     *
     * @return Result
     */
    public function today(array $params = array())
    {
        return $this->client->get('/movies/cinemas/today', $params);
    }

    /**
     * Today in Moldova's cinemas, keyless, kept 10 minutes by the server. 0 credits.
     *
     * @return Result
     */
    public function board(array $params = array())
    {
        return $this->client->get('/movies/board', $params);
    }
}
