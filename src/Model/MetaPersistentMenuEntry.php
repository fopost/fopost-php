<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One locale's menu; `default` is the fallback every language uses. */
final class MetaPersistentMenuEntry extends Model
{
    /** @param array<int, MetaMenuItem> $callToActions */
    private function __construct(
        array $raw,
        public readonly string $locale,
        public readonly array $callToActions,
        public readonly ?bool $composerInputDisabled,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'locale') ?? 'default',
            MetaMenuItem::listFrom(self::seq($data, 'call_to_actions')),
            self::bool($data, 'composer_input_disabled'),
        );
    }
}
