<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A message the bot put somewhere. */
final class DiscordMessageRef extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $channelId,
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
        );
    }
}
