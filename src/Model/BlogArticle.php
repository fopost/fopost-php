<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One article as the list endpoints return it; get() returns the body too, as BlogArticleFull. */
final class BlogArticle extends Model
{
    /** @param array<int, string> $tags */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $keyword,
        public readonly ?string $excerpt,
        public readonly ?string $metaTitle,
        public readonly ?string $metaDescription,
        public readonly ?BlogArticleImage $featuredImage,
        public readonly array $tags,
        public readonly int $wordCount,
        /** queued, writing, draft, scheduled, publishing, published, failed or rejected. */
        public readonly string $status,
        /** plan, user or agent. */
        public readonly string $origin,
        public readonly ?DateTimeImmutable $reviewUntil,
        public readonly ?DateTimeImmutable $publishAt,
        public readonly ?DateTimeImmutable $publishedAt,
        public readonly ?string $publishedUrl,
        public readonly ?string $error,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $featured = self::nested($data, 'featuredImage');

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'title'),
            self::requiredStr($data, 'slug'),
            self::str($data, 'keyword'),
            self::str($data, 'excerpt'),
            self::str($data, 'metaTitle'),
            self::str($data, 'metaDescription'),
            $featured === null ? null : BlogArticleImage::fromArray($featured),
            array_values(array_map(strval(...), self::seq($data, 'tags'))),
            self::int($data, 'wordCount') ?? 0,
            self::str($data, 'status') ?? 'queued',
            self::str($data, 'origin') ?? 'user',
            self::date($data, 'reviewUntil'),
            self::date($data, 'publishAt'),
            self::date($data, 'publishedAt'),
            self::str($data, 'publishedUrl'),
            self::str($data, 'error'),
            self::date($data, 'createdAt'),
            self::date($data, 'updatedAt'),
        );
    }
}
