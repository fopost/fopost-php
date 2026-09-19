<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A boost or standalone ad created through FoPost. */
final class Ad extends Model
{
    /**
     * @param array<string, mixed> $targeting
     * @param array<string, mixed>|null $creative
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $workspaceId,
        public readonly string $kind,
        public readonly string $name,
        public readonly string $goal,
        public readonly string $status,
        public readonly ?string $effectiveStatus,
        public readonly ?string $connectionId,
        public readonly ?string $accountId,
        public readonly ?string $platform,
        public readonly ?string $adAccountId,
        public readonly ?string $sourcePostId,
        public readonly ?int $budgetMinor,
        public readonly ?string $budgetType,
        public readonly ?string $currency,
        public readonly ?DateTimeImmutable $endAt,
        public readonly array $targeting,
        public readonly ?array $creative,
        public readonly ?AdInsights $insights,
        public readonly ?DateTimeImmutable $insightsAt,
        public readonly ?string $lastError,
        public readonly ?DateTimeImmutable $createdAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $insights = self::nested($data, 'insights');

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'workspace_id'),
            self::requiredStr($data, 'kind'),
            self::requiredStr($data, 'name'),
            self::requiredStr($data, 'goal'),
            self::requiredStr($data, 'status'),
            self::str($data, 'effective_status'),
            self::str($data, 'connection_id'),
            self::str($data, 'account_id'),
            self::str($data, 'platform'),
            self::str($data, 'ad_account_id'),
            self::str($data, 'source_post_id'),
            self::int($data, 'budget_minor'),
            self::str($data, 'budget_type'),
            self::str($data, 'currency'),
            self::date($data, 'end_at'),
            self::map($data, 'targeting'),
            self::nested($data, 'creative'),
            $insights !== null ? AdInsights::fromArray($insights) : null,
            self::date($data, 'insights_at'),
            self::str($data, 'last_error'),
            self::date($data, 'created_at'),
        );
    }
}
