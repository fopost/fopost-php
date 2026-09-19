<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Lifetime delivery numbers from the last refresh. Spend is in the ad account currency, minor units. */
final class AdInsights extends Model
{
    private function __construct(
        array $raw,
        public readonly int $impressions,
        public readonly int $reach,
        public readonly int $clicks,
        public readonly int $spendMinor,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'impressions') ?? 0,
            self::int($data, 'reach') ?? 0,
            self::int($data, 'clicks') ?? 0,
            self::int($data, 'spend_minor') ?? 0,
        );
    }
}
