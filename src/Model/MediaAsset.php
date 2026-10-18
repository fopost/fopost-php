<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A file in the media library. */
final class MediaAsset extends Model
{
    /**
     * @param array<int, string> $tags
     * @param array{url: string, created_at: string, created_by_name: ?string, source: string}|null $share
     *   The live share link; present only when listing with `shared: true`.
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $type,
        public readonly string $name,
        /** The asset's identity; store this on a post. */
        public readonly string $url,
        /** Permanent, membership-checked read URL. */
        public readonly ?string $previewUrl,
        public readonly ?int $size,
        public readonly ?string $workspaceId,
        public readonly ?string $visibility,
        public readonly ?string $mimeType,
        public readonly ?string $altText,
        public readonly ?string $altTextSource,
        public readonly array $tags,
        public readonly ?string $folderId,
        public readonly ?string $origin,
        public readonly ?string $sourceUrl,
        public readonly ?DateTimeImmutable $syncedAt,
        public readonly ?string $brandRole,
        public readonly ?string $brandVoiceId,
        public readonly ?string $generationRunId,
        public readonly ?bool $hasPersonConsent,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?array $share,
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
            self::str($data, 'workspace_id'),
            self::str($data, 'visibility'),
            self::str($data, 'mime_type'),
            self::str($data, 'alt_text'),
            self::str($data, 'alt_text_source'),
            array_values(array_map(strval(...), self::seq($data, 'tags'))),
            self::str($data, 'folder_id'),
            self::str($data, 'origin'),
            self::str($data, 'source_url'),
            self::date($data, 'synced_at'),
            self::str($data, 'brand_role'),
            self::str($data, 'brand_voice_id'),
            self::str($data, 'generation_run_id'),
            self::bool($data, 'has_person_consent'),
            self::date($data, 'created_at'),
            self::nested($data, 'share'),
        );
    }
}
