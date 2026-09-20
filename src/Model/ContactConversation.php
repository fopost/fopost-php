<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One thread a contact appears in. */
final class ContactConversation extends Model
{
    private function __construct(
        array $raw,
        /** How the inbox groups it: DM thread id, else root post id, else handle. */
        public readonly string $key,
        public readonly string $accountId,
        public readonly ?string $accountUsername,
        public readonly string $platform,
        public readonly int $messages,
        public readonly int $received,
        public readonly int $sent,
        public readonly ?DateTimeImmutable $lastMessageAt,
        public readonly ?string $lastItemId,
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
            self::str($data, 'account_username'),
            self::requiredStr($data, 'platform'),
            self::int($data, 'messages') ?? 0,
            self::int($data, 'received') ?? 0,
            self::int($data, 'sent') ?? 0,
            self::date($data, 'last_message_at'),
            self::str($data, 'last_item_id'),
        );
    }
}
