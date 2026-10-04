<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A person in the connected server; $id is the member id for a DM or a role. */
final class DiscordMember extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $username,
        public readonly ?string $displayName,
        public readonly ?string $nick,
        public readonly ?string $avatar,
        public readonly bool $isBot,
        /** @var array<int, string> */
        public readonly array $roles,
        public readonly ?string $joinedAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'username') ?? '',
            self::str($data, 'display_name'),
            self::str($data, 'nick'),
            self::str($data, 'avatar'),
            self::bool($data, 'is_bot') ?? false,
            array_values(array_filter(self::seq($data, 'roles'), 'is_string')),
            self::str($data, 'joined_at'),
        );
    }
}
