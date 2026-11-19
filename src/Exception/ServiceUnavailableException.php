<?php

namespace Satelit\Exception;

/**
 * 503 busy. Retried by the client when it carries Retry-After.
 */
class ServiceUnavailableException extends ApiException
{
}
