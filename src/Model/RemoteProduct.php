<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A product on a connected store. $price is the lowest variant price. */
final class RemoteProduct extends Model
{
    /** @param array<int, string> $tags */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $handle,
        /** One of active, draft, archived. */
        public readonly string $status,
        public readonly ?string $description,
        public readonly ?string $vendor,
        public readonly ?string $productType,
        public readonly array $tags,
        public readonly ?string $imageUrl,
        public readonly ?string $url,
        public readonly ?string $price,
        public readonly ?string $currency,
        public readonly ?DateTimeImmutable $updatedAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'title'),
            self::str($data, 'handle'),
            self::requiredStr($data, 'status'),
            self::str($data, 'description'),
            self::str($data, 'vendor'),
            self::str($data, 'product_type'),
            array_values(array_filter(self::seq($data, 'tags'), 'is_string')),
            self::str($data, 'image_url'),
            self::str($data, 'url'),
            self::str($data, 'price'),
            self::str($data, 'currency'),
            self::date($data, 'updated_at'),
        );
    }
}
