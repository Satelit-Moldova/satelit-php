<?php

namespace Satelit\Exception;

use Satelit\RateInfo;

/**
 * An error answer of the API: {"error": {code, message, docs}}.
 * getCode() is the HTTP status; getErrorCode() the API's code.
 */
class ApiException extends SatelitException
{
    /** @var int */
    private $status;
    /** @var string */
    private $errorCode;
    /** @var string|null */
    private $docs;
    /** @var string[] */
    private $fields;
    /** @var string|null */
    private $choose;
    /** @var int|null */
    private $retryAfter;
    /** @var RateInfo */
    private $rate;
    /** @var string */
    private $path;
    /** @var string */
    private $apiMessage;

    /**
     * @param int $status
     * @param string $errorCode
     * @param string $message
     * @param array $extra docs, fields, choose
     * @param RateInfo $rate
     * @param string $path the request path, never the key
     */
    public function __construct($status, $errorCode, $message, array $extra, RateInfo $rate, $path)
    {
        $text = sprintf('Satelit API %d %s: %s', $status, $errorCode, $message);
        if (!empty($extra['fields'])) {
            $text .= ' (fields: ' . implode(', ', (array) $extra['fields']) . ')';
        }
        parent::__construct($text . ' [' . $path . ']', (int) $status);
        $this->status = (int) $status;
        $this->errorCode = (string) $errorCode;
        $this->docs = isset($extra['docs']) ? (string) $extra['docs'] : null;
        $this->fields = isset($extra['fields']) ? array_values((array) $extra['fields']) : array();
        $this->choose = isset($extra['choose']) ? (string) $extra['choose'] : null;
        $this->retryAfter = $rate->retryAfter;
        $this->rate = $rate;
        $this->path = (string) $path;
        $this->apiMessage = (string) $message;
    }

    /** @return int HTTP status */
    public function getStatus()
    {
        return $this->status;
    }

    /** @return string auth_required, key_invalid, plan_required, not_found, invalid, rate_limited, ... */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /** @return string the API's message, in the request's language */
    public function getApiMessage()
    {
        return $this->apiMessage;
    }

    /** @return string|null link to the error's documentation */
    public function getDocs()
    {
        return $this->docs;
    }

    /** @return string[] 422: the wrong parameters */
    public function getFields()
    {
        return $this->fields;
    }

    /** @return string|null 402 without a plan: the pricing page */
    public function getChoose()
    {
        return $this->choose;
    }

    /** @return int|null seconds, from Retry-After */
    public function getRetryAfter()
    {
        return $this->retryAfter;
    }

    /** @return RateInfo */
    public function getRateInfo()
    {
        return $this->rate;
    }

    /** @return string */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Builds the right subclass for a status and code.
     *
     * @return ApiException
     */
    public static function create($status, $errorCode, $message, array $extra, RateInfo $rate, $path)
    {
        switch (true) {
            case $status === 401:
                $class = AuthenticationException::class;
                break;
            case $status === 402:
                $class = PlanRequiredException::class;
                break;
            case $status === 403:
                $class = ForbiddenException::class;
                break;
            case $status === 404:
                $class = NotFoundException::class;
                break;
            case $status === 422:
                $class = ValidationException::class;
                break;
            case $status === 429 && $errorCode === 'rate_limited':
                $class = RateLimitedException::class;
                break;
            case $status === 429 && $errorCode === 'quota_exceeded':
                $class = QuotaExceededException::class;
                break;
            case $status === 429 && $errorCode === 'export_running':
                $class = ExportRunningException::class;
                break;
            case $status === 429:
                $class = TooManyRequestsException::class;
                break;
            case $status === 503:
                $class = ServiceUnavailableException::class;
                break;
            case $status >= 500:
                $class = ServerException::class;
                break;
            default:
                $class = ApiException::class;
        }
        return new $class($status, $errorCode, $message, $extra, $rate, $path);
    }
}
