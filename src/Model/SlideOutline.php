<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Carousel copy split from long text: a cover, the slides, a closing call to action and the brand. */
final class SlideOutline extends Model
{
    /**
     * @param array{title?: string, subtitle?: string} $cover
     * @param array<int, array{heading?: string, body?: string}> $slides
     * @param array{heading?: string, body?: string} $cta
     * @param array<string, mixed> $brand colors, fonts and logo_media_id
     */
    private function __construct(
        array $raw,
        public readonly array $cover,
        public readonly array $slides,
        public readonly array $cta,
        public readonly array $brand,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::map($data, 'cover'),
            array_values(array_filter(self::seq($data, 'slides'), 'is_array')),
            self::map($data, 'cta'),
            self::map($data, 'brand'),
        );
    }
}
