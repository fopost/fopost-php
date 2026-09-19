<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One metric reading, as the changes feed reports it. */
final class MetricChange extends Model
{
    private function __construct(
        array $raw,
        public readonly string $accountId,
        public readonly string $platform,
        public readonly string $externalPostId,
        /** Null for a post made natively on the network. */
        public readonly ?string $postId,
        public readonly ?DateTimeImmutable $postedAt,
        public readonly ?DateTimeImmutable $fetchedAt,
        public readonly ?int $impressions,
        public readonly ?int $reach,
        public readonly ?int $engagements,
        public readonly ?int $likes,
        public readonly ?int $comments,
        public readonly ?int $shares,
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
            self::requiredStr($data, 'external_post_id'),
            self::str($data, 'post_id'),
            self::date($data, 'posted_at'),
            self::date($data, 'fetched_at'),
            self::int($data, 'impressions'),
            self::int($data, 'reach'),
            self::int($data, 'engagements'),
            self::int($data, 'likes'),
            self::int($data, 'comments'),
            self::int($data, 'shares'),
        );
    }
}
