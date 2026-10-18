<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Event counts on a pixel over the last 28 days. */
final class AdPixelStats extends Model
{
    /** @param array<int, AdPixelEventCount> $events */
    private function __construct(
        array $raw,
        public readonly string $since,
        public readonly string $until,
        public readonly array $events,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'since'),
            self::requiredStr($data, 'until'),
            AdPixelEventCount::listFrom(self::seq($data, 'events')),
        );
    }
}
