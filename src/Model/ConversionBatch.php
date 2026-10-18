<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A stored conversions batch as listConversionEvents() returns it. */
final class ConversionBatch extends Model
{
    /** @param array<int, ConversionBatchEvent> $events */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $workspaceId,
        public readonly string $connectionId,
        public readonly string $adAccountId,
        public readonly string $pixelId,
        public readonly ?string $idempotencyKey,
        public readonly int $eventCount,
        public readonly int $accepted,
        public readonly array $events,
        public readonly string $createdAt,
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
            self::requiredStr($data, 'connection_id'),
            self::requiredStr($data, 'ad_account_id'),
            self::requiredStr($data, 'pixel_id'),
            self::str($data, 'idempotency_key'),
            self::int($data, 'event_count') ?? 0,
            self::int($data, 'accepted') ?? 0,
            ConversionBatchEvent::listFrom(self::seq($data, 'events')),
            self::requiredStr($data, 'created_at'),
        );
    }
}
