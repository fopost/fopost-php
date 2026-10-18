<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** An X community linked to a connected account. */
final class XCommunity extends Model
{
    private function __construct(
        array $raw,
        /** Internal id; use it to remove the community. */
        public readonly int $id,
        public readonly string $accountId,
        /** The id X gives the community. */
        public readonly string $communityId,
        public readonly string $name,
        public readonly ?int $memberCount,
        public readonly ?string $description,
        public readonly ?string $imageUrl,
        public readonly ?DateTimeImmutable $lastSyncedAt,
        public readonly ?DateTimeImmutable $createdAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'id') ?? 0,
            self::requiredStr($data, 'account_id'),
            self::requiredStr($data, 'community_id'),
            self::requiredStr($data, 'name'),
            self::int($data, 'member_count'),
            self::str($data, 'description'),
            self::str($data, 'image_url'),
            self::date($data, 'last_synced_at'),
            self::date($data, 'created_at'),
        );
    }
}
