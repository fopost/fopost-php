<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The freshest metrics held for a post made outside FoPost. */
final class NativePostMetrics extends Model
{
    private function __construct(
        array $raw,
        public readonly ?int $impressions,
        public readonly ?int $reach,
        public readonly ?int $engagements,
        public readonly ?int $likes,
        public readonly ?int $comments,
        public readonly ?int $shares,
        public readonly ?int $videoViews,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'impressions'),
            self::int($data, 'reach'),
            self::int($data, 'engagements'),
            self::int($data, 'likes'),
            self::int($data, 'comments'),
            self::int($data, 'shares'),
            self::int($data, 'video_views'),
        );
    }
}
