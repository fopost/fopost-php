<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** How many posts went out on one day. */
final class PostingStreakDay extends Model
{
    private function __construct(
        array $raw,
        public readonly string $date,
        public readonly int $count,
        public readonly int $publishedCount,
        public readonly int $failedCount,
        public readonly int $scheduledCount,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'date'),
            self::int($data, 'count') ?? 0,
            self::int($data, 'published_count') ?? 0,
            self::int($data, 'failed_count') ?? 0,
            self::int($data, 'scheduled_count') ?? 0,
        );
    }
}
