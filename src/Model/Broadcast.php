<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One message, sent into conversations the workspace already has. */
final class Broadcast extends Model
{
    /** @param array<string, mixed> $audience */
    private function __construct(
        array $raw,
        public readonly string $id,
        /** Internal only; never sent to anyone. */
        public readonly string $name,
        public readonly string $text,
        public readonly ?string $accountId,
        public readonly array $audience,
        /** draft, scheduled, sending, sent or cancelled. */
        public readonly string $status,
        public readonly ?DateTimeImmutable $scheduledAt,
        public readonly ?DateTimeImmutable $sentAt,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?BroadcastCounts $counts,
        /** Only on a listing that spans workspaces. */
        public readonly ?string $workspaceId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $counts = self::nested($data, 'counts');

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'name') ?? '',
            self::str($data, 'text') ?? '',
            self::str($data, 'account_id'),
            self::map($data, 'audience'),
            self::str($data, 'status') ?? 'draft',
            self::date($data, 'scheduled_at'),
            self::date($data, 'sent_at'),
            self::date($data, 'created_at'),
            $counts === null ? null : BroadcastCounts::fromArray($counts),
            self::str($data, 'workspace_id'),
        );
    }
}
