<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One day of the time series. */
final class AnalyticsSeriesPoint extends Model
{
    private function __construct(
        array $raw,
        public readonly string $date,
        public readonly int $engagements,
        public readonly int $impressions,
        public readonly int $likes,
        public readonly int $comments,
        public readonly int $shares,
        public readonly int $followers,
        public readonly int $posts,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'date'),
            self::int($data, 'engagements') ?? 0,
            self::int($data, 'impressions') ?? 0,
            self::int($data, 'likes') ?? 0,
            self::int($data, 'comments') ?? 0,
            self::int($data, 'shares') ?? 0,
            self::int($data, 'followers') ?? 0,
            self::int($data, 'posts') ?? 0,
        );
    }
}
