<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A custom, lookalike or website audience on an ad account. */
final class Audience extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $subtype,
        public readonly ?string $description,
        public readonly ?int $sizeLower,
        public readonly ?int $sizeUpper,
        public readonly ?string $deliveryStatus,
        public readonly ?string $createdAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'name'),
            self::str($data, 'subtype'),
            self::str($data, 'description'),
            self::int($data, 'size_lower'),
            self::int($data, 'size_upper'),
            self::str($data, 'delivery_status'),
            self::str($data, 'created_at'),
        );
    }
}
