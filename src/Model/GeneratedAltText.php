<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Alt text written for a library image, now stored on it, plus suggested tags. */
final class GeneratedAltText extends Model
{
    /** @param array<int, string> $tags */
    private function __construct(
        array $raw,
        public readonly string $altText,
        public readonly ?string $altTextSource,
        public readonly array $tags,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'alt_text'),
            self::str($data, 'alt_text_source'),
            array_values(array_filter(self::seq($data, 'tags'), 'is_string')),
        );
    }
}
