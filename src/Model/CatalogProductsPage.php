<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One page of catalog products; pass `nextCursor` back as `after` for the next. */
final class CatalogProductsPage extends Model
{
    /** @param array<int, CatalogProduct> $products */
    private function __construct(
        array $raw,
        public readonly array $products,
        public readonly ?string $nextCursor,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            CatalogProduct::listFrom(self::seq($data, 'products')),
            self::str($data, 'next_cursor'),
        );
    }
}
