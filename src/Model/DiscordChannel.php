<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A Discord text channel the bot can post to; $isCurrent marks this account's. */
final class DiscordChannel extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        /** Discord's channel type: 0 text, 5 announcement, 15 forum. */
        public readonly int $type,
        public readonly ?string $parentId,
        public readonly bool $nsfw,
        /** False when a channel permission in Discord shuts the bot out. */
        public readonly bool $canPost,
        public readonly bool $isCurrent,
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
            self::int($data, 'type') ?? 0,
            self::str($data, 'parent_id'),
            self::bool($data, 'nsfw') ?? false,
            self::bool($data, 'can_post') ?? true,
            self::bool($data, 'is_current') ?? false,
        );
    }
}
