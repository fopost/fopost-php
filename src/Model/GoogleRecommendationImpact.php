<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What Google projects applying a recommendation would change. */
final class GoogleRecommendationImpact extends Model
{
    private function __construct(
        array $raw,
        public readonly ?float $baseClicks,
        public readonly ?float $potentialClicks,
        public readonly ?int $baseCostMinor,
        public readonly ?int $potentialCostMinor,
        public readonly ?float $baseConversions,
        public readonly ?float $potentialConversions,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::float($data, 'baseClicks'),
            self::float($data, 'potentialClicks'),
            self::int($data, 'baseCostMinor'),
            self::int($data, 'potentialCostMinor'),
            self::float($data, 'baseConversions'),
            self::float($data, 'potentialConversions'),
        );
    }
}
