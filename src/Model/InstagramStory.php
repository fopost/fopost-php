<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A story still inside its 24 hours; $insights is present only when asked for. */
final class InstagramStory extends Model
{
    /**
     * @param array<string, mixed>|null $insights
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $mediaType,
        public readonly ?string $mediaProductType,
        public readonly ?string $permalink,
        public readonly ?string $mediaUrl,
        public readonly ?string $thumbnailUrl,
        public readonly ?string $caption,
        public readonly ?string $timestamp,
        public readonly ?array $insights,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'media_type'),
            self::str($data, 'media_product_type'),
            self::str($data, 'permalink'),
            self::str($data, 'media_url'),
            self::str($data, 'thumbnail_url'),
            self::str($data, 'caption'),
            self::str($data, 'timestamp'),
            self::nested($data, 'insights'),
        );
    }
}
