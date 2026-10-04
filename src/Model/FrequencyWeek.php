<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One week of GET /analytics/frequency. */
final class FrequencyWeek extends Model
{
    private function __construct(
        array $raw,
        /** Monday of the week, UTC, as YYYY-MM-DD. */
        public readonly string $weekStart,
        public readonly int $posts,
        public readonly int $engagements,
        public readonly float $avgEngagementsPerPost,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'week_start'),
            self::int($data, 'posts') ?? 0,
            self::int($data, 'engagements') ?? 0,
            self::num($data, 'avg_engagements_per_post') ?? 0.0,
        );
    }
}
