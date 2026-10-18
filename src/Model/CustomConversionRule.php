<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Which pixel event and URL a custom conversion counts. */
final class CustomConversionRule extends Model
{
    private function __construct(
        array $raw,
        public readonly string $eventName,
        public readonly ?string $urlContains,
        public readonly ?string $urlEquals,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'event_name') ?? 'PageView',
            self::str($data, 'url_contains'),
            self::str($data, 'url_equals'),
        );
    }
}
