<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A comment, mention or direct message on a connected account. */
final class InboxItem extends Model
{
    /**
     * @param array<int, InboxAttachment> $attachments
     * @param array<string, mixed>|null $post
     * @param array<string, mixed>|null $postContext
     * @param array<string, mixed>|null $account
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $workspaceId,
        public readonly string $platform,
        public readonly string $type,
        public readonly string $state,
        public readonly ?string $direction,
        public readonly ?string $conversationId,
        public readonly ?string $authorName,
        public readonly ?string $authorHandle,
        public readonly ?string $authorAvatarUrl,
        public readonly ?string $text,
        public readonly array $attachments,
        public readonly ?string $permalink,
        public readonly ?string $postExternalId,
        public readonly ?string $parentExternalId,
        public readonly ?DateTimeImmutable $platformCreatedAt,
        public readonly ?DateTimeImmutable $snoozedUntil,
        public readonly ?DateTimeImmutable $repliedAt,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?bool $canReply,
        public readonly ?bool $hidden,
        public readonly ?bool $canHide,
        public readonly ?bool $canDelete,
        public readonly ?array $post,
        public readonly ?array $postContext,
        public readonly ?array $account,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'workspace_id'),
            self::requiredStr($data, 'platform'),
            self::requiredStr($data, 'type'),
            self::requiredStr($data, 'state'),
            self::str($data, 'direction'),
            self::str($data, 'conversation_id'),
            self::str($data, 'author_name'),
            self::str($data, 'author_handle'),
            self::str($data, 'author_avatar_url'),
            self::str($data, 'text'),
            InboxAttachment::listFrom(self::seq($data, 'attachments')),
            self::str($data, 'permalink'),
            self::str($data, 'post_external_id'),
            self::str($data, 'parent_external_id'),
            self::date($data, 'platform_created_at'),
            self::date($data, 'snoozed_until'),
            self::date($data, 'replied_at'),
            self::date($data, 'created_at'),
            self::bool($data, 'can_reply'),
            self::bool($data, 'hidden'),
            self::bool($data, 'can_hide'),
            self::bool($data, 'can_delete'),
            self::nested($data, 'post'),
            self::nested($data, 'post_context'),
            self::nested($data, 'account'),
        );
    }
}
