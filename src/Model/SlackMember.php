<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A person in the connected Slack workspace; $id is the handle for starting a DM. */
final class SlackMember extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $realName,
        public readonly ?string $displayName,
        public readonly ?string $avatar,
        public readonly bool $isBot,
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
            self::str($data, 'real_name'),
            self::str($data, 'display_name'),
            self::str($data, 'avatar'),
            self::bool($data, 'is_bot') ?? false,
        );
    }
}
