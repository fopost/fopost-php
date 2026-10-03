<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The nickname and avatar the bot wears in the server; null means its own. */
final class DiscordIdentity extends Model
{
    private function __construct(
        array $raw,
        public readonly ?string $username,
        public readonly ?string $avatarUrl,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'username'),
            self::str($data, 'avatar_url'),
        );
    }
}
