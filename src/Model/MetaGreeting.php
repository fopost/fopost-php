<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The greeting set on one account, one entry per locale. */
final class MetaGreeting extends Model
{
    /** @param array<int, MetaGreetingText> $greeting */
    private function __construct(
        array $raw,
        public readonly array $greeting,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, MetaGreetingText::listFrom(self::seq($data, 'greeting')));
    }
}
