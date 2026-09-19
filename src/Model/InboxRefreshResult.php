<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What a manual inbox poll found. */
final class InboxRefreshResult extends Model
{
    /** @param array<int, array<string, mixed>> $dmReconnect */
    private function __construct(
        array $raw,
        public readonly int $accountsPolled,
        public readonly int $newItems,
        public readonly int $rateLimited,
        public readonly array $dmReconnect,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'accounts_polled') ?? 0,
            self::int($data, 'new_items') ?? 0,
            self::int($data, 'rate_limited') ?? 0,
            self::seq($data, 'dm_reconnect'),
        );
    }
}
