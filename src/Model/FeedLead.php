<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A lead stored from a subscribed Page. `fields` is a list of `{name, values}` rows. */
final class FeedLead extends Model
{
    /** @param array<int, array<string, mixed>> $fields */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $leadId,
        public readonly ?string $connectionId,
        public readonly ?string $pageId,
        public readonly ?string $formId,
        public readonly ?string $adId,
        public readonly ?string $adName,
        public readonly ?string $campaignName,
        public readonly ?string $platform,
        public readonly bool $isOrganic,
        public readonly array $fields,
        public readonly ?DateTimeImmutable $submittedAt,
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
            self::requiredStr($data, 'lead_id'),
            self::str($data, 'connection_id'),
            self::str($data, 'page_id'),
            self::str($data, 'form_id'),
            self::str($data, 'ad_id'),
            self::str($data, 'ad_name'),
            self::str($data, 'campaign_name'),
            self::str($data, 'platform'),
            self::bool($data, 'is_organic') ?? false,
            self::seq($data, 'fields'),
            self::date($data, 'submitted_at'),
            self::str($data, 'workspace_id'),
        );
    }
}
