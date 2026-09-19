<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One entry in a Telegram bot's command menu. */
final class TelegramBotCommand extends Model
{
    private function __construct(
        array $raw,
        public readonly string $command,
        public readonly string $description,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::requiredStr($data, 'command'), self::requiredStr($data, 'description'));
    }
}
