<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One account's share of the overview, with its daily follower history. */
final class AnalyticsAccountTotal extends Model
{
    /** @param array<int, array{date: string, followers: int|null}> $history Up to 30 days, oldest first. */
    private function __construct(
        array $raw,
        public readonly string $accountId,
        public readonly string $platform,
        public readonly ?string $username,
        public readonly ?string $name,
        public readonly ?string $avatar,
        public readonly ?int $followers,
        public readonly ?int $totalPosts,
        public readonly ?DateTimeImmutable $fetchedAt,
        public readonly array $history,
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
            self::str($data, 'username'),
            self::str($data, 'name'),
            self::str($data, 'avatar'),
            self::int($data, 'followers'),
            self::int($data, 'total_posts'),
            self::date($data, 'fetched_at'),
            array_values(array_filter(self::seq($data, 'history'), is_array(...))),
        );
    }
}
