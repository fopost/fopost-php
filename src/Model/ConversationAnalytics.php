<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Inbox analytics broken out per thread. */
final class ConversationAnalytics extends Model
{
    /** @param array<int, ConversationAnalyticsRow> $conversations */
    private function __construct(
        array $raw,
        public readonly array $conversations,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            ConversationAnalyticsRow::listFrom(self::seq($data, 'conversations')),
            self::int($data, 'total') ?? 0,
            self::int($data, 'page') ?? 1,
            self::int($data, 'per_page') ?? 25,
        );
    }
}
