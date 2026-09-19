<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What moved between one timeline point and the one before it. */
final class TimelineDelta extends Model
{
    private function __construct(
        array $raw,
        public readonly int $impressions,
        public readonly int $reach,
        public readonly int $engagements,
        public readonly int $likes,
        public readonly int $comments,
        public readonly int $shares,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'impressions') ?? 0,
            self::int($data, 'reach') ?? 0,
            self::int($data, 'engagements') ?? 0,
            self::int($data, 'likes') ?? 0,
            self::int($data, 'comments') ?? 0,
            self::int($data, 'shares') ?? 0,
        );
    }
}
