<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One page of X search results; pass `nextToken` back as `next_token` for the next. */
final class XSearchPage extends Model
{
    /** @param array<int, XPost> $posts */
    private function __construct(
        array $raw,
        public readonly array $posts,
        public readonly ?string $nextToken,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $meta = self::nested($data, 'meta') ?? [];

        return new self(
            $data,
            XPost::listFrom(self::seq($data, 'data')),
            self::str($meta, 'next_token'),
        );
    }
}
