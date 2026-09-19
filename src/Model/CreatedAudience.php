<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A newly created audience. `added` counts the customer list rows the platform accepted. */
final class CreatedAudience extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?int $added,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::requiredStr($data, 'id'), self::int($data, 'added'));
    }
}
