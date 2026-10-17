<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One page of an ad's comments; pass `nextCursor` back as `after`. */
final class AdCommentsPage extends Model
{
    /**
     * @param array<int, AdComment> $comments
     */
    private function __construct(
        array $raw,
        public readonly array $comments,
        public readonly ?string $nextCursor,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            AdComment::listFrom(self::seq($data, 'comments')),
            self::str($data, 'next_cursor'),
        );
    }
}
