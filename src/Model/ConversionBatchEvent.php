<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One stored event of a batch; identifiers are kept only as hashes. */
final class ConversionBatchEvent extends Model
{
    private function __construct(
        array $raw,
        public readonly string $eventName,
        public readonly string $occurredAt,
        public readonly ?string $eventId,
        public readonly string $actionSource,
        public readonly ?string $emailHash,
        public readonly ?string $phoneHash,
        public readonly ?string $externalIdHash,
        public readonly ?int $valueMinor,
        public readonly ?string $currency,
        public readonly ?string $orderId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'event_name'),
            self::requiredStr($data, 'occurred_at'),
            self::str($data, 'event_id'),
            self::str($data, 'action_source') ?? 'website',
            self::str($data, 'email_hash'),
            self::str($data, 'phone_hash'),
            self::str($data, 'external_id_hash'),
            self::int($data, 'value_minor'),
            self::str($data, 'currency'),
            self::str($data, 'order_id'),
        );
    }
}
