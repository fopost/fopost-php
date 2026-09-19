<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The item after a reply was sent, plus where the reply landed on the platform. */
final class InboxReplyResult extends Model
{
    private function __construct(
        array $raw,
        public readonly InboxItem $item,
        public readonly ?string $externalId,
        public readonly ?string $externalUrl,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $reply = self::map($data, 'reply');

        return new self(
            $data,
            InboxItem::fromArray(self::nested($data, 'item')),
            self::str($reply, 'external_id'),
            self::str($reply, 'external_url'),
        );
    }
}
