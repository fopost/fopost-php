<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/**
 * One of Google's own recommendations for the account. The id is the Google
 * resource name rather than the `~` form other objects use, because a
 * recommendation is not an object you address again: it is what apply and
 * dismiss take.
 */
final class GoogleRecommendation extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $type,
        public readonly ?string $campaignId,
        public readonly ?string $adGroupId,
        public readonly bool $dismissed,
        public readonly ?GoogleRecommendationImpact $impact,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $impact = $data['impact'] ?? null;

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'type'),
            self::str($data, 'campaignId'),
            self::str($data, 'adGroupId'),
            self::bool($data, 'dismissed') ?? false,
            is_array($impact) ? GoogleRecommendationImpact::fromArray($impact) : null,
        );
    }
}
