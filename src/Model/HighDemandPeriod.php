<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A window the network should expect heavier spend over. */
final class HighDemandPeriod extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $startAt,
        public readonly ?string $endAt,
        public readonly ?int $budgetValue,
        public readonly ?string $budgetValueType,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'start_at'),
            self::str($data, 'end_at'),
            self::int($data, 'budget_value'),
            self::str($data, 'budget_value_type'),
        );
    }
}
