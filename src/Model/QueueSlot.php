<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A weekly posting slot; queued posts take the next free one. */
final class QueueSlot extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $workspaceId,
        /** Null when the slot applies to every account. */
        public readonly ?string $accountId,
        /** 0 = Sunday … 6 = Saturday. */
        public readonly int $weekday,
        /** HH:MM, 24-hour, in `timezone`. */
        public readonly string $time,
        public readonly string $timezone,
        public readonly ?DateTimeImmutable $createdAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'workspace_id'),
            self::str($data, 'account_id'),
            self::int($data, 'weekday') ?? 0,
            self::requiredStr($data, 'time'),
            self::requiredStr($data, 'timezone'),
            self::date($data, 'created_at'),
        );
    }
}
