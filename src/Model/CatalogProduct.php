<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One product in a catalog. priceMinor is minor units of currency. */
final class CatalogProduct extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $retailerId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $availability,
        public readonly ?string $condition,
        public readonly ?int $priceMinor,
        public readonly ?string $currency,
        public readonly ?string $imageUrl,
        public readonly ?string $url,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'retailer_id'),
            self::requiredStr($data, 'name'),
            self::str($data, 'description'),
            self::str($data, 'availability'),
            self::str($data, 'condition'),
            self::int($data, 'price_minor'),
            self::str($data, 'currency'),
            self::str($data, 'image_url'),
            self::str($data, 'url'),
        );
    }
}
