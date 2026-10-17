<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A caption track on a video. */
final class YouTubeCaptionTrack extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $language,
        public readonly ?string $name,
        public readonly ?string $trackKind,
        public readonly bool $isDraft,
        public readonly bool $isAutoSynced,
        public readonly ?string $lastUpdated,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'language'),
            self::str($data, 'name'),
            self::str($data, 'track_kind'),
            self::bool($data, 'is_draft') ?? false,
            self::bool($data, 'is_auto_synced') ?? false,
            self::str($data, 'last_updated'),
        );
    }
}
