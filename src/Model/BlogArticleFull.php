<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** An article with its body, outline, brief and images; what getArticle() and updateArticle() return. */
final class BlogArticleFull extends Model
{
    /**
     * @param array<int, string> $tags
     * @param array<int, string> $outline
     * @param array<string, mixed>|null $brief
     * @param array<int, BlogArticleImage> $images
     */
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
        public readonly ?string $instructions,
        public readonly array $outline,
        /** The research the writer worked from. */
        public readonly ?array $brief,
        public readonly string $bodyMarkdown,
        public readonly string $bodyHtml,
        public readonly array $images,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $summary = BlogArticle::fromArray($data);

        return new self(
            $data,
            $summary->id,
            $summary->title,
            $summary->slug,
            $summary->keyword,
            $summary->excerpt,
            $summary->metaTitle,
            $summary->metaDescription,
            $summary->featuredImage,
            $summary->tags,
            $summary->wordCount,
            $summary->status,
            $summary->origin,
            $summary->reviewUntil,
            $summary->publishAt,
            $summary->publishedAt,
            $summary->publishedUrl,
            $summary->error,
            $summary->createdAt,
            $summary->updatedAt,
            self::str($data, 'instructions'),
            array_values(array_map(strval(...), self::seq($data, 'outline'))),
            self::nested($data, 'brief'),
            self::str($data, 'bodyMarkdown') ?? '',
            self::str($data, 'bodyHtml') ?? '',
            BlogArticleImage::listFrom(self::seq($data, 'images')),
        );
    }
}
