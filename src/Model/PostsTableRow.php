<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One post of the analytics table with where each delivery stands. */
final class PostsTableRow extends Model
{
    /**
     * @param array<int, array{platform: string, username: string, url: ?string, delivery_status: string}> $platforms
     * @param array<string, int> $deliverySummary total, published, failed, pending
     */
    private function __construct(
        array $raw,
        public readonly string $postId,
        public readonly ?string $preview,
        public readonly ?string $status,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $scheduledAt,
        public readonly array $platforms,
        public readonly array $deliverySummary,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'post_id'),
            self::str($data, 'preview'),
            self::str($data, 'status'),
            self::date($data, 'created_at'),
            self::date($data, 'scheduled_at'),
            array_values(array_filter(self::seq($data, 'platforms'), is_array(...))),
            array_map(intval(...), self::map($data, 'delivery_summary')),
        );
    }
}
