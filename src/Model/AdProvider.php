<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** An ad network from the API's registry. `configured` false cannot be connected yet. */
final class AdProvider extends Model
{
    /**
     * @param array<string, bool>              $capabilities  campaigns, audiences, conversions, forecasts, ...
     * @param array<int, string>               $targetingFacets what searchTargeting() accepts here
     * @param array<int, array<string, mixed>> $trackingMacros macros expanded in a creative's tracking parameters
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $logo,
        public readonly bool $configured,
        /** @var array<int, string> */
        public readonly array $connectMethods,
        public readonly array $capabilities,
        public readonly array $targetingFacets,
        public readonly array $trackingMacros,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'name'),
            self::str($data, 'logo'),
            self::bool($data, 'configured') ?? false,
            array_values(array_filter(self::seq($data, 'connect_methods'), 'is_string')),
            array_filter(self::map($data, 'capabilities'), 'is_bool'),
            array_values(array_filter(self::seq($data, 'targeting_facets'), 'is_string')),
            array_values(array_filter(self::seq($data, 'tracking_macros'), 'is_array')),
        );
    }
}
