<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One locale's greeting, up to 160 characters. */
final class MetaGreetingText extends Model
{
    private function __construct(
        array $raw,
        public readonly string $locale,
        public readonly string $text,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'locale') ?? 'default',
            self::requiredStr($data, 'text'),
        );
    }
}
