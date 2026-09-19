<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One page of leads; pass `nextCursor` back as `after` for the next. */
final class LeadsPage extends Model
{
    /** @param array<int, Lead> $leads */
    private function __construct(
        array $raw,
        public readonly array $leads,
        public readonly ?string $nextCursor,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, Lead::listFrom(self::seq($data, 'leads')), self::str($data, 'next_cursor'));
    }
}
