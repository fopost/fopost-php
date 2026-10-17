<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** Where a workspace's articles publish, how often they are planned and how long a draft waits. */
final class BlogSettings extends Model
{
    private function __construct(
        array $raw,
        public readonly ?string $siteUrl,
        public readonly ?string $topic,
        public readonly ?string $audience,
        public readonly string $language,
        public readonly ?int $locationCode,
        /** Articles planned per week; 0 turns planning off. */
        public readonly int $cadencePerWeek,
        /** Days a draft waits for review before it may auto-publish. */
        public readonly int $reviewHoldDays,
        public readonly bool $autoPublish,
        /** draft, standard or best. */
        public readonly ?string $imageTier,
        public readonly ?int $inBodyImages,
        public readonly ?int $minWords,
        public readonly ?int $maxWords,
        public readonly ?string $brandVoiceId,
        /** product_blog, account or website. */
        public readonly ?string $targetKind,
        public readonly ?string $targetAccountId,
        public readonly ?string $targetPlatform,
        public readonly ?string $websiteUrl,
        public readonly bool $hasWebsiteSecret,
        /** Whether search volume and difficulty can be fetched on this deployment. */
        public readonly bool $keywordDataAvailable,
        public readonly ?DateTimeImmutable $updatedAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'siteUrl'),
            self::str($data, 'topic'),
            self::str($data, 'audience'),
            self::str($data, 'language') ?? 'en',
            self::int($data, 'locationCode'),
            self::int($data, 'cadencePerWeek') ?? 0,
            self::int($data, 'reviewHoldDays') ?? 0,
            self::bool($data, 'autoPublish') ?? false,
            self::str($data, 'imageTier'),
            self::int($data, 'inBodyImages'),
            self::int($data, 'minWords'),
            self::int($data, 'maxWords'),
            self::str($data, 'brandVoiceId'),
            self::str($data, 'targetKind'),
            self::str($data, 'targetAccountId'),
            self::str($data, 'targetPlatform'),
            self::str($data, 'websiteUrl'),
            self::bool($data, 'hasWebsiteSecret') ?? false,
            self::bool($data, 'keywordDataAvailable') ?? false,
            self::date($data, 'updatedAt'),
        );
    }
}
