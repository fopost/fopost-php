<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Daily totals for the period. */
final class AnalyticsTimeSeries extends Model
{
    /** @param array<int, AnalyticsSeriesPoint> $series */
    private function __construct(
        array $raw,
        public readonly int $days,
        public readonly array $series,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'days') ?? 0,
            AnalyticsSeriesPoint::listFrom(self::seq($data, 'series')),
        );
    }
}
