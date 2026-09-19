<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The name and icon a Slack account posts under; null means the app default. */
final class SlackIdentity extends Model
{
    private function __construct(
        array $raw,
        public readonly ?string $username,
        public readonly ?string $iconUrl,
        public readonly ?string $iconEmoji,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'username'),
            self::str($data, 'icon_url'),
            self::str($data, 'icon_emoji'),
        );
    }
}
