<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One account's latest profile numbers inside a workspace. */
final class WorkspaceAccountStats extends Model
{
    private function __construct(
        array $raw,
        public readonly string $accountId,
        public readonly string $platform,
        public readonly ?string $username,
        public readonly ?int $followers,
        public readonly ?int $following,
        public readonly ?int $totalPosts,
        public readonly ?DateTimeImmutable $fetchedAt,
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
            self::int($data, 'followers'),
            self::int($data, 'following'),
            self::int($data, 'total_posts'),
            self::date($data, 'fetched_at'),
        );
    }
}
