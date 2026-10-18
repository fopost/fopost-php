<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One collected reading of an account's profile numbers. */
final class AccountSnapshot extends Model
{
    private function __construct(
        array $raw,
        public readonly ?int $followers,
        public readonly ?int $following,
        public readonly ?int $totalPosts,
        public readonly ?int $reach,
        public readonly ?int $profileViews,
        public readonly ?DateTimeImmutable $fetchedAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'followers'),
            self::int($data, 'following'),
            self::int($data, 'total_posts'),
            self::int($data, 'reach'),
            self::int($data, 'profile_views'),
            self::date($data, 'fetched_at'),
        );
    }
}
