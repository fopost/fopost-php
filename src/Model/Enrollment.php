<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One contact walking one sequence. */
final class Enrollment extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $contactId,
        public readonly ?string $displayName,
        /** Steps already sent, so also the index of the next one. */
        public readonly int $step,
        public readonly ?DateTimeImmutable $nextAt,
        /** active, completed, stopped or failed. */
        public readonly string $status,
        public readonly ?DateTimeImmutable $lastSentAt,
        /** On a skipped step, the reason it was skipped. */
        public readonly ?string $error,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'contact_id'),
            self::str($data, 'display_name'),
            self::int($data, 'step') ?? 0,
            self::date($data, 'next_at'),
            self::str($data, 'status') ?? 'active',
            self::date($data, 'last_sent_at'),
            self::str($data, 'error'),
        );
    }
}
