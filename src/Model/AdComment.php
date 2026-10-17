<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A comment on an ad, read live from the network and never stored. */
final class AdComment extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $adId,
        public readonly string $text,
        public readonly ?string $authorName,
        public readonly ?string $authorAvatarUrl,
        public readonly ?string $createdAt,
        public readonly int $likes,
        public readonly int $replyCount,
        public readonly bool $hidden,
        /** The comment this one answers, when it is not on the ad itself. */
        public readonly ?string $parentId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'ad_id'),
            self::str($data, 'text') ?? '',
            self::str($data, 'author_name'),
            self::str($data, 'author_avatar_url'),
            self::str($data, 'created_at'),
            self::int($data, 'likes') ?? 0,
            self::int($data, 'reply_count') ?? 0,
            self::bool($data, 'hidden') ?? false,
            self::str($data, 'parent_id'),
        );
    }
}
