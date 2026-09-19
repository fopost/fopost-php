<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A file in the media library. */
final class MediaAsset extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $type,
        public readonly string $name,
        public readonly string $url,
        public readonly ?string $previewUrl,
        public readonly ?int $size,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'type'),
            self::requiredStr($data, 'name'),
            self::requiredStr($data, 'url'),
            self::str($data, 'preview_url'),
            self::int($data, 'size'),
        );
    }
}
