<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** How often one event fired on a pixel. */
final class AdPixelEventCount extends Model
{
    private function __construct(
        array $raw,
        public readonly string $eventName,
        public readonly int $count,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::requiredStr($data, 'event_name'), self::int($data, 'count') ?? 0);
    }
}
