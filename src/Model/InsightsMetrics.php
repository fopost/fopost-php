<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Delivery numbers for a period. Spend is in the account currency, minor units; ctr is a percentage. */
final class InsightsMetrics extends Model
{
    private function __construct(
        array $raw,
        public readonly int $impressions,
        public readonly int $reach,
        public readonly int $clicks,
        public readonly int $spendMinor,
        public readonly ?float $ctr,
        public readonly int $leads,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $ctr = self::field($data, 'ctr');

        return new self(
            $data,
            self::int($data, 'impressions') ?? 0,
            self::int($data, 'reach') ?? 0,
            self::int($data, 'clicks') ?? 0,
            self::int($data, 'spend_minor') ?? 0,
            is_int($ctr) || is_float($ctr) ? (float) $ctr : null,
            self::int($data, 'leads') ?? 0,
        );
    }
}
