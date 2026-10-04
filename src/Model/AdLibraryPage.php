<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One page of archive results; pass `nextCursor` back as `after` for the next. */
final class AdLibraryPage extends Model
{
    /** @param array<int, AdLibraryEntry> $entries */
    private function __construct(
        array $raw,
        public readonly array $entries,
        public readonly ?string $nextCursor,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            AdLibraryEntry::listFrom(self::seq($data, 'entries')),
            self::str($data, 'next_cursor'),
        );
    }
}
