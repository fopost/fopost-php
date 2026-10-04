<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** How one thread performed: what it carried, and how long it waited. */
final class ConversationAnalyticsRow extends Model
{
    private function __construct(
        array $raw,
        public readonly string $key,
        public readonly string $accountId,
        public readonly string $platform,
        public readonly int $received,
        public readonly int $sent,
        public readonly int $answered,
        public readonly int $open,
        /** Median minutes to the first reply in this thread. */
        public readonly ?int $medianResponseMinutes,
        public readonly ?DateTimeImmutable $firstMessageAt,
        public readonly ?DateTimeImmutable $lastMessageAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'key'),
            self::requiredStr($data, 'account_id'),
            self::requiredStr($data, 'platform'),
            self::int($data, 'received') ?? 0,
            self::int($data, 'sent') ?? 0,
            self::int($data, 'answered') ?? 0,
            self::int($data, 'open') ?? 0,
            self::int($data, 'median_response_minutes'),
            self::date($data, 'first_message_at'),
            self::date($data, 'last_message_at'),
        );
    }
}
