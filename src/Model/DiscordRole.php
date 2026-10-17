<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A role in the connected server; $permissions is Discord's bitfield as a decimal string. */
final class DiscordRole extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly int $color,
        public readonly bool $hoist,
        public readonly bool $mentionable,
        /** Managed roles belong to an integration and cannot be edited. */
        public readonly bool $managed,
        public readonly int $position,
        public readonly string $permissions,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'name') ?? '',
            self::int($data, 'color') ?? 0,
            self::bool($data, 'hoist') ?? false,
            self::bool($data, 'mentionable') ?? false,
            self::bool($data, 'managed') ?? false,
            self::int($data, 'position') ?? 0,
            self::str($data, 'permissions') ?? '0',
        );
    }
}
