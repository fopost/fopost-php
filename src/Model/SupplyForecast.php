<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What an audience would deliver at a budget, over the network's own window. */
final class SupplyForecast extends Model
{
    private function __construct(
        array $raw,
        public readonly ?string $currency,
        public readonly ?int $impressions,
        public readonly ?int $clicks,
        public readonly ?int $spendMinor,
        public readonly ?int $windowDays,
        public readonly bool $ready,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'currency'),
            self::int($data, 'impressions'),
            self::int($data, 'clicks'),
            self::int($data, 'spend_minor'),
            self::int($data, 'window_days'),
            self::bool($data, 'ready') ?? false,
        );
    }
}
