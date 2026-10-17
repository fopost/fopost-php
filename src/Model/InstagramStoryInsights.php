<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Insights for one story. */
final class InstagramStoryInsights extends Model
{
    /**
     * @param array<int|string, mixed> $insights
     */
    private function __construct(
        array $raw,
        public readonly string $storyId,
        public readonly array $insights,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'story_id'),
            self::map($data, 'insights'),
        );
    }
}
