<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One block of a post's body: text plus its media. */
final class ContentBlock extends Model
{
    /** @param array<int, MediaItem> $media */
    private function __construct(
        array $raw,
        public readonly int|string|null $id,
        public readonly ?string $text,
        public readonly array $media,
        public readonly ?int $position,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $id = self::field($data, 'id');

        return new self(
            $data,
            is_int($id) || is_string($id) ? $id : null,
            self::str($data, 'text'),
            MediaItem::listFrom(self::seq($data, 'media')),
            self::int($data, 'position'),
        );
    }
}
