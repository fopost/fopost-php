<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One direct message thread. */
final class InboxConversation extends Model
{
    /**
     * @param array<string, mixed>|null $participant
     * @param array<string, mixed>|null $account
     */
    private function __construct(
        array $raw,
        public readonly ?string $workspaceId,
        public readonly string $accountId,
        public readonly string $conversationId,
        public readonly int $messageCount,
        public readonly int $unreadCount,
        public readonly ?DateTimeImmutable $lastMessageAt,
        public readonly ?string $lastMessageText,
        public readonly ?bool $lastMessageOutbound,
        public readonly ?array $participant,
        public readonly ?array $account,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'workspace_id'),
            self::requiredStr($data, 'account_id'),
            self::requiredStr($data, 'conversation_id'),
            self::int($data, 'message_count') ?? 0,
            self::int($data, 'unread_count') ?? 0,
            self::date($data, 'last_message_at'),
            self::str($data, 'last_message_text'),
            self::bool($data, 'last_message_outbound'),
            self::nested($data, 'participant'),
            self::nested($data, 'account'),
        );
    }
}
