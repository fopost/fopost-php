<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One page of the stored leads feed; pass `nextCursor` back as `cursor` for the next. */
final class LeadsFeedPage extends Model
{
    /** @param array<int, FeedLead> $leads */
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

        return new self($data, FeedLead::listFrom(self::seq($data, 'leads')), self::str($data, 'next_cursor'));
    }
}
