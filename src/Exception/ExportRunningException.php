<?php

namespace Satelit\Exception;

/**
 * 429 export_running: this account already has an export running.
 */
class ExportRunningException extends TooManyRequestsException
{
}
