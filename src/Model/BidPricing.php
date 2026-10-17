<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What the auction costs, in minor units of the ad account currency. */
final class BidPricing extends Model
{
    private function __construct(
        array $raw,
        public readonly ?string $currency,
        public readonly ?int $suggestedBidMinor,
        public readonly ?int $minBidMinor,
        public readonly ?int $maxBidMinor,
        public readonly ?int $dailyBudgetFloorMinor,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'currency'),
            self::int($data, 'suggested_bid_minor'),
            self::int($data, 'min_bid_minor'),
            self::int($data, 'max_bid_minor'),
            self::int($data, 'daily_budget_floor_minor'),
        );
    }
}
