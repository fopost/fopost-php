<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** How engagement accumulates as a post ages. */
final class ContentDecayReport extends Model
{
    /** @param array<int, DecayBand> $bands */
    private function __construct(
        array $raw,
        public readonly int $days,
        public readonly int $postsMeasured,
        /** First band where the average post had passed half its final engagement. */
        public readonly ?string $halfLifeBucket,
        public readonly array $bands,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'days') ?? 0,
            self::int($data, 'posts_measured') ?? 0,
            self::str($data, 'half_life_bucket'),
            DecayBand::listFrom(self::seq($data, 'bands')),
        );
    }
}
