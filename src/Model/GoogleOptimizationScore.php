<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Google's estimate of how well the account is set up, from 0 to 1. */
final class GoogleOptimizationScore extends Model
{
    /** @param array<int, GoogleOptimizationScoreCampaign> $campaigns */
    private function __construct(
        array $raw,
        public readonly ?float $score,
        public readonly ?float $weight,
        public readonly array $campaigns,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::float($data, 'score'),
            self::float($data, 'weight'),
            GoogleOptimizationScoreCampaign::listFrom($data['campaigns'] ?? []),
        );
    }
}
