<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One attempt to deliver an event to a webhook. */
final class WebhookDelivery extends Model
{
    /** @param array<string, mixed>|null $payload The body exactly as it was sent; only on the single read. */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $webhookId,
        public readonly string $event,
        /** The X-FoPost-Delivery id; every attempt of one event shares it. */
        public readonly string $deliveryKey,
        /** 1 for the first send; retries and redeliveries count up. */
        public readonly int $attempt,
        /** success or failed. */
        public readonly string $status,
        /** Null when no response arrived. */
        public readonly ?int $responseStatus,
        public readonly ?string $responseSnippet,
        public readonly int $durationMs,
        public readonly ?string $payloadHash,
        public readonly ?DateTimeImmutable $nextRetryAt,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?array $payload,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'webhook_id'),
            self::requiredStr($data, 'event'),
            self::requiredStr($data, 'delivery_key'),
            self::int($data, 'attempt') ?? 1,
            self::requiredStr($data, 'status'),
            self::int($data, 'response_status'),
            self::str($data, 'response_snippet'),
            self::int($data, 'duration_ms') ?? 0,
            self::str($data, 'payload_hash'),
            self::date($data, 'next_retry_at'),
            self::date($data, 'created_at'),
            self::nested($data, 'payload'),
        );
    }
}
