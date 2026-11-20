<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A design: a story, post, carousel, thumbnail, card or collage. */
final class Design extends Model
{
    /** @param array<string, mixed>|null $document */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly string $kind,
        public readonly ?int $width,
        public readonly ?int $height,
        /** The design document as a plain map; null on list rows, which leave it out. */
        public readonly ?array $document,
        public readonly ?string $thumbnailMediaId,
        public readonly ?string $thumbnailUrl,
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
            self::requiredStr($data, 'name'),
            self::requiredStr($data, 'kind'),
            self::int($data, 'width'),
            self::int($data, 'height'),
            self::nested($data, 'document'),
            self::str($data, 'thumbnail_media_id'),
            self::str($data, 'thumbnail_url'),
            self::date($data, 'created_at'),
            self::date($data, 'updated_at'),
        );
    }
}
