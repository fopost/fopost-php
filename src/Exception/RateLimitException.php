<?php

declare(strict_types=1);

namespace Fopost\Sdk\Exception;

/** 429: rate limit exceeded. retryAfter is in seconds when the API sends it. */
class RateLimitException extends FopostException
{
    public function __construct(
        string $message,
        int $status = 429,
        ?string $errorCode = null,
        mixed $body = null,
        public readonly ?float $retryAfter = null,
    ) {
        parent::__construct($message, $status, $errorCode, $body);
    }

    public function getRetryAfter(): ?float
    {
        return $this->retryAfter;
    }
}
