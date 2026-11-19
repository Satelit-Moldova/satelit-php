<?php

namespace Satelit\Cache;

/**
 * How long each answer is kept, by path (without /v1). Movies cost 40 credits
 * a call, so their details are kept a day. 0 means "never cache".
 */
final class Ttl
{
    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 86400;

    /**
     * @param string $path such as "/rates" or "/movies/titles/m603"
     * @return int seconds
     */
    public static function forPath($path)
    {
        $p = (string) $path;
        if ($p === '/me' || self::starts($p, array('/feed', '/export'))) {
            return 0;
        }
        if ($p === '/movies/genres') {
            return 7 * self::DAY;
        }
        if (self::starts($p, array('/movies/titles/')) && substr($p, -10) === '/showtimes') {
            return 2 * self::HOUR;
        }
        if (self::starts($p, array('/movies/titles/', '/movies/people/'))) {
            return self::DAY;
        }
        if (self::starts($p, array('/movies/search', '/movies/discover', '/movies/trending'))) {
            return 6 * self::HOUR;
        }
        if (self::starts($p, array('/movies/'))) {
            return 2 * self::HOUR; // cinemas, schedules, today
        }
        if (self::starts($p, array('/news'))) {
            return 10 * self::MINUTE;
        }
        if (self::starts($p, array('/sample', '/status'))) {
            return 5 * self::MINUTE;
        }
        if (self::starts($p, array('/courts'))) {
            return 30 * self::MINUTE;
        }
        if (self::starts($p, array('/rates'))) {
            return 3 * self::HOUR;
        }
        if (self::starts($p, array('/fuel', '/prices', '/deals', '/flights', '/power', '/law', '/jokes', '/products', '/plans', '/stats'))) {
            return self::HOUR;
        }
        if (self::starts($p, array('/tours', '/indexes', '/market', '/citizenship', '/companies', '/recipes'))) {
            return 6 * self::HOUR;
        }
        if (self::starts($p, array('/tariffs', '/rest', '/estate', '/places'))) {
            return 12 * self::HOUR;
        }
        if (self::starts($p, array('/delivery', '/insurance', '/geo', '/catalog'))) {
            return self::DAY;
        }
        return 10 * self::MINUTE;
    }

    private static function starts($p, array $prefixes)
    {
        foreach ($prefixes as $prefix) {
            if (strncmp($p, $prefix, strlen($prefix)) === 0) {
                return true;
            }
        }
        return false;
    }
}
