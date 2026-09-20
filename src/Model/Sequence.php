<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A series of messages, each a delay after the one before. */
final class Sequence extends Model
{
    /** @param array<int, SequenceStep> $steps */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $accountId,
        public readonly array $steps,
        /** active or paused. A paused sequence fires nothing. */
        public readonly string $status,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?SequenceEnrollmentCounts $enrollments,
        /** Only on a listing that spans workspaces. */
        public readonly ?string $workspaceId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $enrollments = self::nested($data, 'enrollments');

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'name') ?? '',
            self::str($data, 'account_id'),
            SequenceStep::listFrom(self::seq($data, 'steps')),
            self::str($data, 'status') ?? 'active',
            self::date($data, 'created_at'),
            $enrollments === null ? null : SequenceEnrollmentCounts::fromArray($enrollments),
            self::str($data, 'workspace_id'),
        );
    }
}
