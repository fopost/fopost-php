<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A tappable prompt Messenger or Instagram shows before the first message. */
final class MetaIceBreaker extends Model
{
    private function __construct(
        array $raw,
        public readonly string $question,
        public readonly string $payload,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'question'),
            self::requiredStr($data, 'payload'),
        );
    }
}
