<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** An article that already lives on a connected site, by the platform's own id. */
final class RemoteArticle extends Model
{
    /** @param array<int, string> $tags */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $blogId,
        public readonly string $title,
        public readonly ?string $bodyHtml,
        public readonly ?string $excerpt,
        /** One of published, draft, pending, scheduled. */
        public readonly string $status,
        public readonly ?string $authorName,
        public readonly array $tags,
        public readonly ?string $imageUrl,
        public readonly ?string $url,
        public readonly ?DateTimeImmutable $publishedAt,
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
            self::str($data, 'blog_id'),
            self::requiredStr($data, 'title'),
            self::str($data, 'body_html'),
            self::str($data, 'excerpt'),
            self::requiredStr($data, 'status'),
            self::str($data, 'author_name'),
            array_values(array_filter(self::seq($data, 'tags'), 'is_string')),
            self::str($data, 'image_url'),
            self::str($data, 'url'),
            self::date($data, 'published_at'),
            self::date($data, 'updated_at'),
        );
    }
}
