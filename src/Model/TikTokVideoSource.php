<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One of the account's own videos, resolved from a share link. */
final class TikTokVideoSource extends Model
{
    private function __construct(
        array $raw,
        public readonly string $videoId,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?int $durationSec,
        public readonly ?string $coverImageUrl,
        public readonly ?string $shareUrl,
        public readonly ?string $embedLink,
        /** What a repurpose run reads. TikTok serves no raw media file. */
        public readonly ?string $downloadUrl,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'video_id') ?? '',
            self::str($data, 'title'),
            self::str($data, 'description'),
            self::int($data, 'duration_sec'),
            self::str($data, 'cover_image_url'),
            self::str($data, 'share_url'),
            self::str($data, 'embed_link'),
            self::str($data, 'download_url'),
        );
    }
}
