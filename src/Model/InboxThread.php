<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One platform post and the comments it has collected. */
final class InboxThread extends Model
{
    /**
     * @param array<string, mixed>|null $post
     * @param array<string, mixed>|null $account
     */
    private function __construct(
        array $raw,
        public readonly ?string $workspaceId,
        public readonly string $accountId,
        public readonly ?string $postExternalId,
        public readonly int $commentCount,
        public readonly int $unreadCount,
        public readonly ?DateTimeImmutable $lastCommentAt,
        public readonly ?string $lastCommentText,
        public readonly ?string $lastCommentAuthor,
        public readonly ?array $post,
        public readonly ?array $account,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'workspace_id'),
            self::requiredStr($data, 'account_id'),
            self::str($data, 'post_external_id'),
            self::int($data, 'comment_count') ?? 0,
            self::int($data, 'unread_count') ?? 0,
            self::date($data, 'last_comment_at'),
            self::str($data, 'last_comment_text'),
            self::str($data, 'last_comment_author'),
            self::nested($data, 'post'),
            self::nested($data, 'account'),
        );
    }
}
