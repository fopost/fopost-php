<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** What the on-demand refresh did for one delivery. */
final class CollectPostDelivery extends Model
{
    private function __construct(
        array $raw,
        public readonly string $accountId,
        public readonly string $platform,
        public readonly string $externalPostId,
        public readonly bool $collected,
        public readonly ?DateTimeImmutable $fetchedAt,
        /** Why the refresh did not happen. */
        public readonly ?string $message,
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
            self::bool($data, 'collected') ?? false,
            self::date($data, 'fetched_at'),
            self::str($data, 'message'),
        );
    }
}
