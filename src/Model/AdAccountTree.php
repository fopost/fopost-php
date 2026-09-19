<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Every campaign on an ad account with its ad sets and ads, read live. */
final class AdAccountTree extends Model
{
    /** @param array<int, AdCampaign> $campaigns */
    private function __construct(
        array $raw,
        public readonly string $adAccountId,
        public readonly ?string $currency,
        public readonly ?string $workspaceId,
        public readonly array $campaigns,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'ad_account_id'),
            self::str($data, 'currency'),
            self::str($data, 'workspace_id'),
            AdCampaign::listFrom(self::seq($data, 'campaigns')),
        );
    }
}
