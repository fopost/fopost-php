<?php

declare(strict_types=1);

namespace Fopost\Sdk\Exception;

/** 402: no active subscription, or AI credits exhausted. */
class PaymentRequiredException extends FopostException
{
    /** Where the API suggests sending the user to upgrade. */
    public function getUpgradeUrl(): ?string
    {
        if (is_array($this->body) && isset($this->body['upgrade_url']) && is_string($this->body['upgrade_url'])) {
            return $this->body['upgrade_url'];
        }

        return null;
    }
}
