<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One age band of GET /analytics/decay. */
final class DecayBand extends Model
{
    private function __construct(
        array $raw,
        public readonly string $bucket,
        public readonly string $label,
        /** Posts with at least one reading in this band. */
        public readonly int $posts,
        public readonly float $avgEngagements,
        public readonly float $avgImpressions,
        /** Mean share of the post's final engagement reached by this age, 0-1. */
        public readonly ?float $shareOfFinal,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'bucket'),
            self::requiredStr($data, 'label'),
            self::int($data, 'posts') ?? 0,
            self::num($data, 'avg_engagements') ?? 0.0,
            self::num($data, 'avg_impressions') ?? 0.0,
            self::num($data, 'share_of_final'),
        );
    }
}
