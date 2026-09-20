<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One retrieved passage, with the source it came from so a reply can cite it. */
final class KnowledgeMatch extends Model
{
    private function __construct(
        array $raw,
        public readonly string $sourceId,
        public readonly string $sourceTitle,
        public readonly string $sourceKind,
        public readonly ?string $sourceUrl,
        public readonly string $text,
        /** Similarity to the question, 0-1. */
        public readonly float $score,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'source_id'),
            self::requiredStr($data, 'source_title'),
            self::requiredStr($data, 'source_kind'),
            self::str($data, 'source_url'),
            self::requiredStr($data, 'text'),
            (float) (self::field($data, 'score') ?? 0),
        );
    }
}
