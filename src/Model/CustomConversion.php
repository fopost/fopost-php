<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A custom conversion on a pixel. */
final class CustomConversion extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $pixelId,
        public readonly string $eventType,
        public readonly ?CustomConversionRule $rule,
        public readonly ?string $description,
        public readonly ?int $defaultValueMinor,
        public readonly bool $isArchived,
        public readonly bool $isUnavailable,
        public readonly ?string $createdAt,
        public readonly ?string $lastFiredAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $rule = self::nested($data, 'rule');

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'name'),
            self::str($data, 'pixel_id'),
            self::requiredStr($data, 'event_type'),
            $rule !== null ? CustomConversionRule::fromArray($rule) : null,
            self::str($data, 'description'),
            self::int($data, 'default_value_minor'),
            self::bool($data, 'is_archived') ?? false,
            self::bool($data, 'is_unavailable') ?? false,
            self::str($data, 'created_at'),
            self::str($data, 'last_fired_at'),
        );
    }
}
