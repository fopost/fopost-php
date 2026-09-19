<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** An ad inside an ad set, read live from the network. `id` is the network's id. */
final class NetworkAd extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $campaignId,
        public readonly ?string $adSetId,
        public readonly ?string $creativeId,
        public readonly string $status,
        public readonly ?string $effectiveStatus,
        public readonly ?string $createdAt,
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
            self::str($data, 'ad_set_id'),
            self::str($data, 'creative_id'),
            self::requiredStr($data, 'status'),
            self::str($data, 'effective_status'),
            self::str($data, 'created_at'),
        );
    }
}
