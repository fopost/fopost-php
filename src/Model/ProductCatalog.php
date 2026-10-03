<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A product catalog on the connection's business portfolio, read live. */
final class ProductCatalog extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $vertical,
        public readonly ?int $productCount,
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
            self::str($data, 'vertical'),
            self::int($data, 'product_count'),
        );
    }
}
