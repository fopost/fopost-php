<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The command menu a Telegram bot shows in a connected chat. */
final class TelegramBotCommands extends Model
{
    /** @param array<int, TelegramBotCommand> $commands */
    private function __construct(
        array $raw,
        public readonly array $commands,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, TelegramBotCommand::listFrom(self::seq($data, 'commands')));
    }
}
