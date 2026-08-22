<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A post: its content blocks, its target accounts, and its schedule. */
final class Post extends Model
{
    /**
     * @param array<int, ContentBlock> $content
     * @param array<int, PostAccount> $accounts
     * @param array<int, Label> $labels
     * @param array<string, mixed> $settings
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $status,
        public readonly ?string $workspaceId,
        public readonly ?string $contentType,
        public readonly ?DateTimeImmutable $scheduleAt,
        public readonly ?string $title,
        public readonly ?string $summary,
        public readonly ?bool $repeatable,
        public readonly ?int $repeatableTimes,
        public readonly ?int $repeatableGap,
        public readonly ?string $repeatableGapUnit,
        public readonly ?int $remainingPosts,
        public readonly ?bool $autoPlug,
        public readonly ?string $autoPlugContent,
        public readonly ?DateTimeImmutable $approvedAt,
        public readonly ?string $rejectionReason,
        public readonly array $content,
        public readonly array $accounts,
        public readonly array $labels,
        public readonly array $settings,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'status'),
            self::str($data, 'workspace_id'),
            self::str($data, 'content_type'),
            self::date($data, 'schedule_at'),
            self::str($data, 'title'),
            self::str($data, 'summary'),
            self::bool($data, 'repeatable'),
            self::int($data, 'repeatable_times'),
            self::int($data, 'repeatable_gap'),
            self::str($data, 'repeatable_gap_unit'),
            self::int($data, 'remaining_posts'),
            self::bool($data, 'auto_plug'),
            self::str($data, 'auto_plug_content'),
            self::date($data, 'approved_at'),
            self::str($data, 'rejection_reason'),
            ContentBlock::listFrom(self::seq($data, 'content')),
            PostAccount::listFrom(self::seq($data, 'accounts')),
            Label::listFrom(self::seq($data, 'labels')),
            self::map($data, 'settings'),
            self::date($data, 'created_at'),
            self::date($data, 'updated_at'),
        );
    }

    /** Every content block's text, joined. Convenience for simple posts. */
    public function text(): string
    {
        $parts = [];
        foreach ($this->content as $block) {
            if ($block->text !== null && $block->text !== '') {
                $parts[] = $block->text;
            }
        }

        return implode("\n\n", $parts);
    }
}
