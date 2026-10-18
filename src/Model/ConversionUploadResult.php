<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What came of a conversions batch; `replayed` means the Idempotency-Key had been used before. */
final class ConversionUploadResult extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly int $accepted,
        public readonly int $eventCount,
        public readonly bool $replayed,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::int($data, 'accepted') ?? 0,
            self::int($data, 'event_count') ?? 0,
            self::bool($data, 'replayed') ?? false,
        );
    }
}
