<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** An ad on a connected ad account that was made outside FoPost. Read live, never stored. */
final class ExternalAd extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $effectiveStatus,
        public readonly ?string $campaignId,
        public readonly ?string $campaignName,
        public readonly ?string $objective,
        public readonly ?int $budgetMinor,
        public readonly ?string $budgetType,
        public readonly ?DateTimeImmutable $endAt,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?string $connectionId,
        public readonly ?string $adAccountId,
        public readonly ?string $currency,
        public readonly ?string $workspaceId,
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
            self::str($data, 'effective_status'),
            self::str($data, 'campaign_id'),
            self::str($data, 'campaign_name'),
            self::str($data, 'objective'),
            self::int($data, 'budget_minor'),
            self::str($data, 'budget_type'),
            self::date($data, 'end_at'),
            self::date($data, 'created_at'),
            self::str($data, 'connection_id'),
            self::str($data, 'ad_account_id'),
            self::str($data, 'currency'),
            self::str($data, 'workspace_id'),
        );
    }
}
