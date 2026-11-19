<?php

namespace Satelit\Exception;

/**
 * 429 rate_limited: too many requests this minute. Retried by the client.
 */
class RateLimitedException extends TooManyRequestsException
{
}
