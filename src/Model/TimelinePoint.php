<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One reading of a post, with what moved since the reading before it. */
final class TimelinePoint extends Model
{
    private function __construct(
        array $raw,
        public readonly ?DateTimeImmutable $at,
        /** Minutes since publication; null when the network never said when. */
        public readonly ?int $ageMinutes,
        public readonly ?int $impressions,
        public readonly ?int $reach,
        public readonly ?int $engagements,
        public readonly ?int $likes,
        public readonly ?int $comments,
        public readonly ?int $shares,
        public readonly ?int $videoViews,
        public readonly TimelineDelta $delta,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::date($data, 'at'),
            self::int($data, 'age_minutes'),
            self::int($data, 'impressions'),
            self::int($data, 'reach'),
            self::int($data, 'engagements'),
            self::int($data, 'likes'),
            self::int($data, 'comments'),
            self::int($data, 'shares'),
            self::int($data, 'video_views'),
            TimelineDelta::fromArray(self::nested($data, 'delta') ?? []),
        );
    }
}
