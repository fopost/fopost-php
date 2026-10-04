<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One contact on one broadcast, and what became of their message. */
final class BroadcastRecipient extends Model
{
    private function __construct(
        array $raw,
        public readonly string $contactId,
        public readonly ?string $displayName,
        /** pending, sent, skipped or failed. */
        public readonly string $status,
        /**
         * Why nothing was sent: window_closed, no_conversation or
         * unsupported_platform. window_closed means the network's messaging
         * window had shut, so nothing was attempted.
         */
        public readonly ?string $skipReason,
        public readonly ?DateTimeImmutable $sentAt,
        public readonly ?string $error,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'contact_id'),
            self::str($data, 'display_name'),
            self::str($data, 'status') ?? 'pending',
            self::str($data, 'skip_reason'),
            self::date($data, 'sent_at'),
            self::str($data, 'error'),
        );
    }
}
