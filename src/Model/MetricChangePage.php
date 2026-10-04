<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** Readings since a cursor, with the cursor to pass next time. */
final class MetricChangePage extends Model
{
    /** @param array<int, MetricChange> $changes */
    private function __construct(
        array $raw,
        public readonly ?DateTimeImmutable $since,
        /** Feed back as `since` to continue; null when nothing changed. */
        public readonly ?DateTimeImmutable $cursor,
        public readonly bool $hasMore,
        public readonly array $changes,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::date($data, 'since'),
            self::date($data, 'cursor'),
            self::bool($data, 'has_more') ?? false,
            MetricChange::listFrom(self::seq($data, 'changes')),
        );
    }
}
