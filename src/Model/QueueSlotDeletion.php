<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What deleting a slot did to the posts queued on it. */
final class QueueSlotDeletion extends Model
{
    /** @param array<int, QueueMove> $moved */
    private function __construct(
        array $raw,
        public readonly bool $deleted,
        public readonly array $moved,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::bool($data, 'deleted') ?? false, QueueMove::listFrom(self::seq($data, 'moved')));
    }
}
