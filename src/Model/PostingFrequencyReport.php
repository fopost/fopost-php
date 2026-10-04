<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Weekly cadence set against what each cadence earned per post. */
final class PostingFrequencyReport extends Model
{
    /**
     * @param array<int, FrequencyWeek> $weeks
     * @param array<int, FrequencyBand> $bands
     */
    private function __construct(
        array $raw,
        public readonly int $days,
        public readonly array $weeks,
        public readonly array $bands,
        /** The cadence that earned the most per post; null without posts. */
        public readonly ?FrequencyBand $best,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $best = self::nested($data, 'best');

        return new self(
            $data,
            self::int($data, 'days') ?? 0,
            FrequencyWeek::listFrom(self::seq($data, 'weeks')),
            FrequencyBand::listFrom(self::seq($data, 'bands')),
            $best !== null ? FrequencyBand::fromArray($best) : null,
        );
    }
}
