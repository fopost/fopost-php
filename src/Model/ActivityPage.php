<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One page of activity, newest first; pass `nextCursor` back as `cursor` for the next. */
final class ActivityPage extends Model
{
    /** @param array<int, ActivityEvent> $events */
    private function __construct(
        array $raw,
        public readonly array $events,
        public readonly ?string $nextCursor,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $meta = self::nested($data, 'meta') ?? [];

        return new self(
            $data,
            ActivityEvent::listFrom(self::seq($data, 'data')),
            self::str($meta, 'next_cursor'),
        );
    }
}
