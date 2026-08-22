<?php

declare(strict_types=1);

namespace Fopost\Sdk\Exception;

/** 400 or 422: the request body failed validation. */
class ValidationException extends FopostException
{
    /**
     * Field level errors, when the API sends them.
     *
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        if (is_array($this->body) && isset($this->body['errors']) && is_array($this->body['errors'])) {
            return $this->body['errors'];
        }

        return [];
    }
}
