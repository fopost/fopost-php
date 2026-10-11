<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/**
 * The `data` of an article.published delivery.
 *
 * Image URLs are fetchable for six hours; download and store your own copy.
 */
final class BlogArticleWebhookPayload extends Model
{
    /**
     * @param array<int, BlogArticleImage> $images
     * @param array<int, string> $tags
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $excerpt,
        public readonly ?string $metaTitle,
        public readonly ?string $metaDescription,
        public readonly string $bodyMarkdown,
        public readonly string $bodyHtml,
        public readonly ?BlogArticleImage $featuredImage,
        public readonly array $images,
        public readonly array $tags,
        public readonly ?string $keyword,
        public readonly int $wordCount,
        public readonly ?DateTimeImmutable $publishedAt,
        /** Where the article went live; null on an article.test delivery. */
        public readonly ?string $publishedUrl,
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
            self::str($data, 'excerpt'),
            self::str($data, 'metaTitle'),
            self::str($data, 'metaDescription'),
            self::str($data, 'bodyMarkdown') ?? '',
            self::str($data, 'bodyHtml') ?? '',
            $featured === null ? null : BlogArticleImage::fromArray($featured),
            BlogArticleImage::listFrom(self::seq($data, 'images')),
            array_values(array_map(strval(...), self::seq($data, 'tags'))),
            self::str($data, 'keyword'),
            self::int($data, 'wordCount') ?? 0,
            self::date($data, 'publishedAt'),
            self::str($data, 'publishedUrl'),
        );
    }
}
