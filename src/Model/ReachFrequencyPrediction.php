<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A priced flight. Nothing is bought until it is reserved. */
final class ReachFrequencyPrediction extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $name,
        public readonly ?string $status,
        public readonly ?int $reach,
        public readonly ?int $impressions,
        public readonly ?int $frequencyCap,
        /** Account currency, minor units. */
        public readonly ?int $budgetMinor,
        public readonly ?string $startAt,
        public readonly ?string $endAt,
        /** True once the prediction holds inventory. */
        public readonly bool $reserved,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'name'),
            self::str($data, 'status'),
            self::int($data, 'reach'),
            self::int($data, 'impressions'),
            self::int($data, 'frequency_cap'),
            self::int($data, 'budget_minor'),
            self::str($data, 'start_at'),
            self::str($data, 'end_at'),
            self::bool($data, 'reserved') ?? false,
        );
    }
}
