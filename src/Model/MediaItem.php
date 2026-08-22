<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One image, video, or gif attached to a content block. */
final class MediaItem extends Model
{
    private function __construct(
        array $raw,
        public readonly string $type,
        public readonly string $url,
        public readonly ?string $name,
        public readonly ?int $size,
        public readonly ?string $alt,
        public readonly ?string $thumbnail,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'type'),
            self::requiredStr($data, 'url'),
            self::str($data, 'name'),
            self::int($data, 'size'),
            self::str($data, 'alt'),
            self::str($data, 'thumbnail'),
        );
    }
}
