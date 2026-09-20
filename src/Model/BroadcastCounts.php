<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What became of a broadcast's recipients, by status. */
final class BroadcastCounts extends Model
{
    private function __construct(
        array $raw,
        public readonly int $total,
        public readonly int $sent,
        /** Usually the messaging window doing its job. */
        public readonly int $skipped,
        public readonly int $failed,
        public readonly int $pending,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'total') ?? 0,
            self::int($data, 'sent') ?? 0,
            self::int($data, 'skipped') ?? 0,
            self::int($data, 'failed') ?? 0,
            self::int($data, 'pending') ?? 0,
        );
    }
}
