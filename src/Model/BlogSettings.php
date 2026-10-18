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
        /** traffic, leads, sales, signups or authority. */
        public readonly ?string $goal,
        /** What the blog wants to write about. */
        public readonly ?string $focusTopics,
        /** What the blog never writes about. */
        public readonly ?string $avoidTopics,
        public readonly string $language,
        public readonly ?int $locationCode,
        /** Articles planned per week; 0 turns planning off. */
        public readonly int $cadencePerWeek,
        /** Days before its planned date that a picked keyword starts being written. */
        public readonly int $reviewHoldDays,
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
        /** Null before the first run. */
        public readonly ?BlogPlanRun $planRun,
        /** Whether search volume and difficulty can be fetched on this deployment. */
        public readonly bool $keywordDataAvailable,
        public readonly ?DateTimeImmutable $updatedAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $planRun = self::nested($data, 'planRun');

        return new self(
            $data,
            self::str($data, 'siteUrl'),
            self::str($data, 'topic'),
            self::str($data, 'audience'),
            self::str($data, 'goal'),
            self::str($data, 'focusTopics'),
            self::str($data, 'avoidTopics'),
            self::str($data, 'language') ?? 'en',
            self::int($data, 'locationCode'),
            self::int($data, 'cadencePerWeek') ?? 0,
            self::int($data, 'reviewHoldDays') ?? 0,
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
            $planRun === null ? null : BlogPlanRun::fromArray($planRun),
            self::bool($data, 'keywordDataAvailable') ?? false,
            self::date($data, 'updatedAt'),
        );
    }
}
