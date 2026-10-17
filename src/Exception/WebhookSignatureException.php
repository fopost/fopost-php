<?php

declare(strict_types=1);

namespace Fopost\Sdk\Exception;

/** A webhook delivery whose signature, timestamp or body could not be trusted. */
class WebhookSignatureException extends FopostException
{
    public function __construct(string $message, string $errorCode)
    {
        parent::__construct($message, 0, $errorCode);
    }
}
