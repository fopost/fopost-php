<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What a conversion rule recorded over a date range. */
final class ConversionMetrics extends Model
{
    private function __construct(
        array $raw,
        public readonly int $conversions,
        public readonly int $postClickConversions,
        public readonly int $viewThroughConversions,
        public readonly int $valueMinor,
        public readonly ?int $costPerConversionMinor,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'conversions') ?? 0,
            self::int($data, 'post_click_conversions') ?? 0,
            self::int($data, 'view_through_conversions') ?? 0,
            self::int($data, 'value_minor') ?? 0,
            self::int($data, 'cost_per_conversion_minor'),
        );
    }
}
