<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/**
 * A campaign, read live from the network. `budgetMinor` is null when the budget lives on the
 * ad sets; `adSets` is filled only inside an account tree.
 */
final class AdCampaign extends Model
{
    /** @param array<int, AdSet> $adSets */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly string $status,
        public readonly ?string $effectiveStatus,
        public readonly ?string $objective,
        public readonly ?int $budgetMinor,
        public readonly ?string $budgetType,
        public readonly ?string $createdAt,
        public readonly array $adSets,
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
            self::requiredStr($data, 'status'),
            self::str($data, 'effective_status'),
            self::str($data, 'objective'),
            self::int($data, 'budget_minor'),
            self::str($data, 'budget_type'),
            self::str($data, 'created_at'),
            AdSet::listFrom(self::seq($data, 'ad_sets')),
        );
    }
}
