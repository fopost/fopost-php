<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The network's match score for one event, out of 10, and which identifiers drove it. */
final class EventMatchQuality extends Model
{
    /** @param array<int, EventMatchKey> $matchKeys */
    private function __construct(
        array $raw,
        public readonly string $eventName,
        public readonly ?float $score,
        public readonly array $matchKeys,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'event_name'),
            self::float($data, 'score'),
            EventMatchKey::listFrom(self::seq($data, 'match_keys')),
        );
    }
}
