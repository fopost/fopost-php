<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One page of ad-library results; pass `nextCursor` back as the cursor. */
final class AdLibraryPage extends Model
{
    /** @param array<int, AdLibraryAd> $ads */
    private function __construct(
        array $raw,
        public readonly array $ads,
        public readonly ?string $nextCursor,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            AdLibraryAd::listFrom(self::seq($data, 'ads')),
            self::str($data, 'next_cursor'),
        );
    }
}
