<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Insights for one object over a date range, optionally broken down and per day. */
final class AdInsightsReport extends Model
{
    /**
     * @param array<int, InsightsRow> $breakdown
     * @param array<int, InsightsRow> $timeline
     */
    private function __construct(
        array $raw,
        public readonly string $objectId,
        public readonly ?string $currency,
        public readonly string $since,
        public readonly string $until,
        public readonly ?string $breakdownBy,
        public readonly ?InsightsMetrics $totals,
        public readonly array $breakdown,
        public readonly array $timeline,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $totals = self::nested($data, 'totals');

        return new self(
            $data,
            self::requiredStr($data, 'object_id'),
            self::str($data, 'currency'),
            self::requiredStr($data, 'since'),
            self::requiredStr($data, 'until'),
            self::str($data, 'breakdown_by'),
            $totals !== null ? InsightsMetrics::fromArray($totals) : null,
            InsightsRow::listFrom(self::seq($data, 'breakdown')),
            InsightsRow::listFrom(self::seq($data, 'timeline')),
        );
    }
}
