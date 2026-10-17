<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A conversion action on the account. */
final class GoogleConversionAction extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly string $category,
        public readonly string $status,
        public readonly string $type,
        public readonly ?string $countingType,
        public readonly ?int $valueMinor,
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
            self::requiredStr($data, 'category'),
            self::requiredStr($data, 'status'),
            self::requiredStr($data, 'type'),
            self::str($data, 'countingType'),
            self::int($data, 'valueMinor'),
        );
    }
}
