<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One campaign's optimization score. */
final class GoogleOptimizationScoreCampaign extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?float $score,
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
            self::float($data, 'score'),
        );
    }
}
