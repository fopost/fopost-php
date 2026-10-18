<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Who the audience is, by age, gender, country and city. */
final class AudienceDemographics extends Model
{
    /**
     * @param array<int, DemographicsBucket> $age
     * @param array<int, DemographicsBucket> $gender
     * @param array<int, DemographicsBucket> $country
     * @param array<int, DemographicsBucket> $city
     * @param array<int, array{account_id: string, platform: string, username: string}> $contributingAccounts
     * @param array<int, array{account_id: string, platform: string, username: string}> $unsupportedAccounts
     */
    private function __construct(
        array $raw,
        /** followers, engaged or reached. */
        public readonly string $audience,
        public readonly array $age,
        public readonly array $gender,
        public readonly array $country,
        public readonly array $city,
        public readonly array $contributingAccounts,
        public readonly array $unsupportedAccounts,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $dimensions = self::map($data, 'dimensions');

        return new self(
            $data,
            self::requiredStr($data, 'audience'),
            DemographicsBucket::listFrom(self::seq($dimensions, 'age')),
            DemographicsBucket::listFrom(self::seq($dimensions, 'gender')),
            DemographicsBucket::listFrom(self::seq($dimensions, 'country')),
            DemographicsBucket::listFrom(self::seq($dimensions, 'city')),
            array_values(array_filter(self::seq($data, 'contributing_accounts'), is_array(...))),
            array_values(array_filter(self::seq($data, 'unsupported_accounts'), is_array(...))),
        );
    }
}
