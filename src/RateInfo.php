<?php

namespace Satelit;

use Satelit\Http\HttpResponse;

/**
 * What the X-RateLimit-* and X-Credits-* headers said. A field is null when
 * its header was not there (keyless answers, answers from the cache).
 */
final class RateInfo
{
    /** @var int */
    public $statusCode = 0;
    /** @var int|null requests per minute of the plan */
    public $limit;
    /** @var int|null requests left this minute */
    public $remaining;
    /** @var int|null seconds until the minute resets */
    public $reset;
    /** @var int|null what this call cost */
    public $creditsCost;
    /** @var int|null credits of the period */
    public $creditsLimit;
    /** @var int|null credits left in the period */
    public $creditsRemaining;
    /** @var string|null ISO time the period resets */
    public $creditsReset;
    /** @var int|null seconds, from Retry-After */
    public $retryAfter;

    /**
     * @return RateInfo
     */
    public static function fromResponse(HttpResponse $r)
    {
        $ri = new self();
        $ri->statusCode = $r->status;
        $ri->limit = self::int($r->header('x-ratelimit-limit'));
        $ri->remaining = self::int($r->header('x-ratelimit-remaining'));
        $ri->reset = self::int($r->header('x-ratelimit-reset'));
        $ri->creditsCost = self::int($r->header('x-credits-cost'));
        $ri->creditsLimit = self::int($r->header('x-credits-limit'));
        $ri->creditsRemaining = self::int($r->header('x-credits-remaining'));
        $ri->creditsReset = $r->header('x-credits-reset');
        $ri->retryAfter = self::retryAfter($r->header('retry-after'));
        return $ri;
    }

    private static function int($v)
    {
        return ($v !== null && preg_match('/^-?\d+$/', trim($v))) ? (int) $v : null;
    }

    /**
     * Seconds or an HTTP date.
     *
     * @param string|null $v
     * @return int|null
     */
    public static function retryAfter($v)
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (preg_match('/^\d+$/', trim($v))) {
            return (int) $v;
        }
        $t = strtotime($v);
        return $t === false ? null : max(0, $t - time());
    }
}
