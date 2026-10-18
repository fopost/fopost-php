<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** How much of an event's traffic carried one identifier. */
final class EventMatchKey extends Model
{
    private function __construct(
        array $raw,
        public readonly string $identifier,
        public readonly float $coveragePercent,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'identifier'),
            self::float($data, 'coverage_percent') ?? 0.0,
        );
    }
}
