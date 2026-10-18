<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What the posts under one label earned in the period. */
final class LabelAnalytics extends Model
{
    private function __construct(
        array $raw,
        public readonly string $labelId,
        public readonly string $name,
        public readonly ?string $color,
        public readonly int $postCount,
        public readonly int $impressions,
        public readonly int $reach,
        public readonly int $engagements,
        public readonly int $likes,
        public readonly int $comments,
        public readonly int $shares,
        /** Engagements over reach, falling back to impressions; null when neither exists. */
        public readonly ?float $engagementRate,
        /** Follows gained across the campaign, or null when no platform reports it. */
        public readonly ?int $followerDelta,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'label_id'),
            self::requiredStr($data, 'name'),
            self::str($data, 'color'),
            self::int($data, 'post_count') ?? 0,
            self::int($data, 'impressions') ?? 0,
            self::int($data, 'reach') ?? 0,
            self::int($data, 'engagements') ?? 0,
            self::int($data, 'likes') ?? 0,
            self::int($data, 'comments') ?? 0,
            self::int($data, 'shares') ?? 0,
            self::num($data, 'engagement_rate'),
            self::int($data, 'follower_delta'),
        );
    }
}
