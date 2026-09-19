<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** An ad set, read live from the network. `ads` is filled only inside an account tree. */
final class AdSet extends Model
{
    /** @param array<int, NetworkAd> $ads */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $campaignId,
        public readonly string $status,
        public readonly ?string $effectiveStatus,
        public readonly ?int $budgetMinor,
        public readonly ?string $budgetType,
        public readonly ?string $endAt,
        public readonly ?string $optimizationGoal,
        public readonly ?string $createdAt,
        public readonly array $ads,
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
            self::str($data, 'campaign_id'),
            self::requiredStr($data, 'status'),
            self::str($data, 'effective_status'),
            self::int($data, 'budget_minor'),
            self::str($data, 'budget_type'),
            self::str($data, 'end_at'),
            self::str($data, 'optimization_goal'),
            self::str($data, 'created_at'),
            NetworkAd::listFrom(self::seq($data, 'ads')),
        );
    }
}
