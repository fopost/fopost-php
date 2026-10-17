<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A track from TikTok's Commercial Music Library; `id` travels as the `music_id` platform setting. */
final class TikTokMusic extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $author,
        public readonly ?int $durationSec,
        public readonly ?string $coverUrl,
        public readonly ?string $previewUrl,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'id') ?? '',
            self::str($data, 'title') ?? '',
            self::str($data, 'author'),
            self::int($data, 'duration_sec'),
            self::str($data, 'cover_url'),
            self::str($data, 'preview_url'),
        );
    }
}
