<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The slice of a catalog one catalog ad runs from. */
final class ProductSet extends Model
{
    /** @param array<string, mixed> $filter */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?int $productCount,
        public readonly array $filter,
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
            self::int($data, 'product_count'),
            self::map($data, 'filter'),
        );
    }
}
