<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A message in the connected channel. */
final class DiscordMessage extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $channelId,
        public readonly string $content,
        public readonly string $authorId,
        public readonly string $authorName,
        public readonly bool $pinned,
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
            self::str($data, 'channel_id') ?? '',
            self::str($data, 'content') ?? '',
            self::str($data, 'author_id') ?? '',
            self::str($data, 'author_name') ?? '',
            self::bool($data, 'pinned') ?? false,
            self::str($data, 'created_at'),
        );
    }
}
