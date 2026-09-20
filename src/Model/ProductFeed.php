<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A product feed that keeps a catalog in step with a hosted file. */
final class ProductFeed extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $url,
        public readonly ?string $schedule,
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
            self::str($data, 'url'),
            self::str($data, 'schedule'),
            self::str($data, 'created_at'),
        );
    }
}
