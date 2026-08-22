<?php

declare(strict_types=1);

namespace Fopost\Sdk\Exception;

use RuntimeException;

/**
 * Base class for every error the FoPost API returns.
 *
 * The API answers with an {"error": "<code>", "message": "<human readable>"}
 * envelope, which maps onto the code and message properties.
 */
class FopostException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?string $errorCode = null,
        public readonly mixed $body = null,
    ) {
        parent::__construct($message, $status);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }

    public function __toString(): string
    {
        $suffix = $this->errorCode !== null ? " ({$this->errorCode})" : '';

        return "[{$this->status}{$suffix}] {$this->getMessage()}";
    }
}
