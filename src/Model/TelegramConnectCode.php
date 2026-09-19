<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A one-time code; send $command (`/connect <code>`) to the bot in a chat to connect it. */
final class TelegramConnectCode extends Model
{
    private function __construct(
        array $raw,
        public readonly string $code,
        public readonly string $command,
        public readonly ?string $botUsername,
        public readonly ?string $deepLink,
        public readonly ?string $groupLink,
        public readonly ?DateTimeImmutable $expiresAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'code'),
            self::requiredStr($data, 'command'),
            self::str($data, 'bot_username'),
            self::str($data, 'deep_link'),
            self::str($data, 'group_link'),
            self::date($data, 'expires_at'),
        );
    }
}
