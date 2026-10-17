<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The platform answers a flow upload with its validation errors, not a refusal. */
final class WhatsappFlowJsonResult extends Model
{
    private function __construct(
        array $raw,
        public readonly bool $success,
        public readonly array $validationErrors,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::bool($data, 'success') ?? false,
            is_array($data['validationErrors'] ?? null) ? $data['validationErrors'] : [],
        );
    }
}
