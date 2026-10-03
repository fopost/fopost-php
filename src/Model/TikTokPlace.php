<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A place a post can be tagged with; `id` travels as the `location_id` platform setting. */
final class TikTokPlace extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $address,
        public readonly ?string $city,
        public readonly ?string $country,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'id') ?? '',
            self::str($data, 'name') ?? '',
            self::str($data, 'address'),
            self::str($data, 'city'),
            self::str($data, 'country'),
        );
    }
}
