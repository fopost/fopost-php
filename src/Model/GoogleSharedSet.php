<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A negative keyword list. */
final class GoogleSharedSet extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly string $type,
        public readonly ?int $memberCount,
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
            self::requiredStr($data, 'type'),
            self::int($data, 'memberCount'),
        );
    }
}
