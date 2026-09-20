<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A Business Center, or the network's equivalent grouping of ad accounts. */
final class AdBusinessCenter extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $role,
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
            self::str($data, 'role'),
        );
    }
}
