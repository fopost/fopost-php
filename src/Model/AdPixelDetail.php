<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A pixel in full; `code` is the script to place on the site. */
final class AdPixelDetail extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $code,
        public readonly ?string $createdAt,
        public readonly ?string $lastFiredAt,
        public readonly bool $isUnavailable,
        public readonly ?string $dataUseSetting,
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
            self::str($data, 'code'),
            self::str($data, 'created_at'),
            self::str($data, 'last_fired_at'),
            self::bool($data, 'is_unavailable') ?? false,
            self::str($data, 'data_use_setting'),
        );
    }
}
