<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** An X post read live with a connected account's token. */
final class XPost extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $text,
        public readonly ?string $permalink,
        public readonly ?string $authorName,
        public readonly ?string $authorHandle,
        public readonly ?string $authorAvatarUrl,
        public readonly ?int $authorFollowers,
        /** Thread root id. */
        public readonly ?string $conversationId,
        /** The post this one replied to. */
        public readonly ?string $parentExternalId,
        public readonly ?int $likes,
        public readonly ?int $replies,
        public readonly ?int $reposts,
        public readonly ?int $quotes,
        public readonly ?DateTimeImmutable $platformCreatedAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'text'),
            self::str($data, 'permalink'),
            self::str($data, 'author_name'),
            self::str($data, 'author_handle'),
            self::str($data, 'author_avatar_url'),
            self::int($data, 'author_followers'),
            self::str($data, 'conversation_id'),
            self::str($data, 'parent_external_id'),
            self::int($data, 'likes'),
            self::int($data, 'replies'),
            self::int($data, 'reposts'),
            self::int($data, 'quotes'),
            self::date($data, 'platform_created_at'),
        );
    }
}
