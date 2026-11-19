<?php

namespace Satelit\Exception;

/**
 * 401: no key (auth_required) or an unknown or revoked key (key_invalid).
 */
class AuthenticationException extends ApiException
{
}
