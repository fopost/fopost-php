<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A label grouping campaigns, ad sets and ads for reporting. */
final class AdLabel extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
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
            self::str($data, 'created_at'),
        );
    }
}
