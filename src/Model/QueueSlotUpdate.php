<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The slot after an edit, plus the queued posts the edit moved. */
final class QueueSlotUpdate extends Model
{
    /** @param array<int, QueueMove> $moved */
    private function __construct(
        array $raw,
        public readonly QueueSlot $slot,
        public readonly array $moved,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, QueueSlot::fromArray($data), QueueMove::listFrom(self::seq($data, 'moved')));
    }
}
