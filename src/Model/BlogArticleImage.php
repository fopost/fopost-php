<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One image an article carries. Webhook URLs are fetchable for six hours only. */
final class BlogArticleImage extends Model
{
    private function __construct(
        array $raw,
        public readonly string $mediaId,
        public readonly string $url,
        public readonly string $alt,
        /** Index of the outline heading the image sits under; null on the featured image. */
        public readonly ?int $position,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'mediaId'),
            self::requiredStr($data, 'url'),
            self::str($data, 'alt') ?? '',
            self::int($data, 'position'),
        );
    }
}
