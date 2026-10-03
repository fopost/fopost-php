<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A track a Reel can carry; $id travels as the audio_id platform setting. */
final class InstagramAudio extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $title,
        public readonly ?string $artist,
        public readonly ?int $durationMs,
        public readonly ?string $audioType,
        public readonly ?string $coverArtworkUrl,
        public readonly ?string $previewUrl,
        public readonly ?string $username,
        public readonly ?bool $isAdsEligible,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'title'),
            self::str($data, 'artist'),
            self::int($data, 'duration_ms'),
            self::str($data, 'audio_type'),
            self::str($data, 'cover_artwork_url'),
            self::str($data, 'preview_url'),
            self::str($data, 'username'),
            self::bool($data, 'is_ads_eligible'),
        );
    }
}
