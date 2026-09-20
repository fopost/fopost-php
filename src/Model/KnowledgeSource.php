<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/**
 * One thing the workspace has told FoPost about itself: an FAQ, a note, a page
 * on its own site, or a plain-text/CSV file from the media library.
 */
final class KnowledgeSource extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        /** `faq`, `text`, `url` or `file`. */
        public readonly string $kind,
        public readonly string $title,
        /** Only a `ready` source is searched. */
        public readonly string $status,
        /** Why the last sync failed, in plain words. */
        public readonly ?string $statusMessage,
        /** Set for `url` sources. */
        public readonly ?string $url,
        /** Set for `file` sources: the media library item read. */
        public readonly ?string $mediaId,
        /** Null means the source serves the whole workspace. */
        public readonly ?string $brandVoiceId,
        /** Searchable passages the last sync produced. */
        public readonly int $chunkCount,
        /** The typed text, for `faq` and `text` sources only. */
        public readonly ?string $content,
        public readonly ?DateTimeImmutable $lastSyncedAt,
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
            self::requiredStr($data, 'kind'),
            self::requiredStr($data, 'title'),
            self::requiredStr($data, 'status'),
            self::str($data, 'status_message'),
            self::str($data, 'url'),
            self::str($data, 'media_id'),
            self::str($data, 'brand_voice_id'),
            self::int($data, 'chunk_count') ?? 0,
            self::str($data, 'content'),
            self::date($data, 'last_synced_at'),
            self::date($data, 'created_at'),
            self::date($data, 'updated_at'),
        );
    }
}
