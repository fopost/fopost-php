<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One delivery's readings: the same post on two networks decays differently. */
final class TimelineDelivery extends Model
{
    /** @param array<int, TimelinePoint> $points */
    private function __construct(
        array $raw,
        public readonly string $accountId,
        public readonly string $platform,
        public readonly string $username,
        public readonly string $externalPostId,
        public readonly ?DateTimeImmutable $postedAt,
        public readonly array $points,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'account_id'),
            self::requiredStr($data, 'platform'),
            self::requiredStr($data, 'username'),
            self::requiredStr($data, 'external_post_id'),
            self::date($data, 'posted_at'),
            TimelinePoint::listFrom(self::seq($data, 'points')),
        );
    }
}
