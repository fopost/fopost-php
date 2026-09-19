<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The DM a new conversation opened, and the message we sent. */
final class InboxStartConversationResult extends Model
{
    private function __construct(
        array $raw,
        public readonly ?string $conversationId,
        public readonly ?InboxItem $item,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $item = self::nested($data, 'item');

        return new self(
            $data,
            self::str($data, 'conversation_id'),
            $item !== null ? InboxItem::fromArray($item) : null,
        );
    }
}
