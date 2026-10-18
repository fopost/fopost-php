<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A post ranked by what it earned in the period. */
final class TopPost extends Model
{
    /**
     * @param array<int, array{platform: string, username: string, url: string|null, removed: bool}> $platforms
     * @param array<int, array{id: string, name: string, color: string}> $labels Always empty for native posts.
     * @param array<string, int> $metrics engagements, impressions, reach, likes, comments, shares, reposts,
     *   clicks, saves, video_views
     */
    private function __construct(
        array $raw,
        public readonly int $rank,
        /** The FoPost post id, or null for a post made natively on the network. */
        public readonly ?string $postId,
        public readonly ?string $externalPostId,
        /** fopost or platform. */
        public readonly string $source,
        public readonly ?string $preview,
        public readonly ?string $permalink,
        public readonly ?string $thumbnailUrl,
        public readonly ?string $status,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly array $platforms,
        public readonly array $labels,
        public readonly array $metrics,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'rank') ?? 0,
            self::str($data, 'post_id'),
            self::str($data, 'external_post_id'),
            self::requiredStr($data, 'source'),
            self::str($data, 'preview'),
            self::str($data, 'permalink'),
            self::str($data, 'thumbnail_url'),
            self::str($data, 'status'),
            self::date($data, 'created_at'),
            array_values(array_filter(self::seq($data, 'platforms'), is_array(...))),
            array_values(array_filter(self::seq($data, 'labels'), is_array(...))),
            array_map(intval(...), self::map($data, 'metrics')),
        );
    }
}
