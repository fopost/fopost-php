<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What POST /validate/media returns. mimeType and type are set only when ok. */
final class MediaValidation extends Model
{
    /** @param array<int, string> $issues */
    private function __construct(
        array $raw,
        public readonly bool $ok,
        public readonly array $issues,
        public readonly string $name,
        public readonly int $size,
        public readonly ?string $mimeType,
        public readonly ?string $type,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::bool($data, 'ok') ?? false,
            array_values(array_filter(self::seq($data, 'issues'), 'is_string')),
            self::requiredStr($data, 'name'),
            self::int($data, 'size') ?? 0,
            self::str($data, 'mime_type'),
            self::str($data, 'type'),
        );
    }
}
