<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A post on the account that never went out through FoPost. */
final class NativePost extends Model
{
    private function __construct(
        array $raw,
        public readonly string $externalPostId,
        public readonly ?string $text,
        public readonly ?string $permalink,
        public readonly ?string $thumbnailUrl,
        public readonly ?string $mediaType,
        public readonly ?DateTimeImmutable $postedAt,
        public readonly ?DateTimeImmutable $fetchedAt,
        public readonly NativePostMetrics $metrics,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'external_post_id'),
            self::str($data, 'text'),
            self::str($data, 'permalink'),
            self::str($data, 'thumbnail_url'),
            self::str($data, 'media_type'),
            self::date($data, 'posted_at'),
            self::date($data, 'fetched_at'),
            NativePostMetrics::fromArray(self::nested($data, 'metrics') ?? []),
        );
    }
}
