<?php

namespace Satelit\Exception;

/**
 * 429 quota_exceeded: the credits of the period are used. Never retried.
 */
class QuotaExceededException extends TooManyRequestsException
{
}
