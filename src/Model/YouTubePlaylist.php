<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A playlist on the channel; $isDefault marks the one a new video joins by default. */
final class YouTubePlaylist extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $privacy,
        public readonly ?int $itemCount,
        public readonly ?string $thumbnailUrl,
        public readonly bool $isDefault,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'title'),
            self::str($data, 'description'),
            self::str($data, 'privacy'),
            self::int($data, 'item_count'),
            self::str($data, 'thumbnail_url'),
            self::bool($data, 'is_default') ?? false,
        );
    }
}
