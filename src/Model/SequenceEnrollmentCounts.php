<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Where a sequence's enrollments stand, by status. */
final class SequenceEnrollmentCounts extends Model
{
    private function __construct(
        array $raw,
        public readonly int $total,
        public readonly int $active,
        public readonly int $completed,
        public readonly int $stopped,
        public readonly int $failed,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'total') ?? 0,
            self::int($data, 'active') ?? 0,
            self::int($data, 'completed') ?? 0,
            self::int($data, 'stopped') ?? 0,
            self::int($data, 'failed') ?? 0,
        );
    }
}
