<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One public archive entry. Read live on every search and stored nowhere. */
final class AdLibraryEntry extends Model
{
    /**
     * @param array<int, string> $bodies
     * @param array<int, string> $titles
     * @param array<int, string> $linkUrls
     * @param array<int, string> $publisherPlatforms
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $pageId,
        public readonly ?string $pageName,
        public readonly array $bodies,
        public readonly array $titles,
        public readonly array $linkUrls,
        public readonly ?string $snapshotUrl,
        public readonly array $publisherPlatforms,
        public readonly ?string $startedAt,
        public readonly ?string $endedAt,
        /** Only on the archive's disclosure entries. */
        public readonly ?string $currency,
        public readonly ?int $spendLower,
        public readonly ?int $spendUpper,
        public readonly ?int $impressionsLower,
        public readonly ?int $impressionsUpper,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'page_id'),
            self::str($data, 'page_name'),
            self::seq($data, 'bodies'),
            self::seq($data, 'titles'),
            self::seq($data, 'link_urls'),
            self::str($data, 'snapshot_url'),
            self::seq($data, 'publisher_platforms'),
            self::str($data, 'started_at'),
            self::str($data, 'ended_at'),
            self::str($data, 'currency'),
            self::int($data, 'spend_lower'),
            self::int($data, 'spend_upper'),
            self::int($data, 'impressions_lower'),
            self::int($data, 'impressions_upper'),
        );
    }
}
