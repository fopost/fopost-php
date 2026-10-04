<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One cadence band of GET /analytics/frequency. */
final class FrequencyBand extends Model
{
    private function __construct(
        array $raw,
        public readonly string $band,
        public readonly string $label,
        public readonly int $weeks,
        public readonly int $posts,
        public readonly float $avgPostsPerWeek,
        public readonly float $avgEngagementsPerPost,
        /** Engagements over reach, impressions as the stand-in; null with neither. */
        public readonly ?float $engagementRate,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'band'),
            self::requiredStr($data, 'label'),
            self::int($data, 'weeks') ?? 0,
            self::int($data, 'posts') ?? 0,
            self::num($data, 'avg_posts_per_week') ?? 0.0,
            self::num($data, 'avg_engagements_per_post') ?? 0.0,
            self::num($data, 'engagement_rate'),
        );
    }
}
