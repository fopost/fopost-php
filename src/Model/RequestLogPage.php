<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One page of request logs, newest first, with the period's totals. */
final class RequestLogPage extends Model
{
    /**
     * @param array<int, RequestLog> $logs
     * @param array<int, RequestLogBucket> $buckets
     */
    private function __construct(
        array $raw,
        public readonly array $logs,
        /** Pass back as `$before` for the next page; null on the last page. */
        public readonly ?string $nextCursor,
        public readonly bool $hasMore,
        public readonly ?DateTimeImmutable $since,
        public readonly int $total,
        public readonly int $errors,
        public readonly array $buckets,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $meta = self::nested($data, 'meta') ?? [];
        $summary = self::nested($data, 'summary') ?? [];

        return new self(
            $data,
            RequestLog::listFrom(self::seq($data, 'data')),
            self::str($meta, 'next_cursor'),
            self::bool($meta, 'has_more') ?? false,
            self::date($summary, 'since'),
            self::int($summary, 'total') ?? 0,
            self::int($summary, 'errors') ?? 0,
            RequestLogBucket::listFrom(self::seq($summary, 'buckets')),
        );
    }
}
