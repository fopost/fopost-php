<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** Credits remaining, used, and total for the current billing period. */
final class AiCreditBalance extends Model
{
    private function __construct(
        array $raw,
        public readonly int $creditsRemaining,
        public readonly int $creditsUsed,
        public readonly int $creditsTotal,
        public readonly ?DateTimeImmutable $periodStart,
        public readonly ?DateTimeImmutable $periodEnd,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'credits_remaining') ?? 0,
            self::int($data, 'credits_used') ?? 0,
            self::int($data, 'credits_total') ?? 0,
            self::date($data, 'period_start'),
            self::date($data, 'period_end'),
        );
    }
}
