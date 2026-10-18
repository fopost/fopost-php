<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A queued post moved to a new time because its slot changed. */
final class QueueMove extends Model
{
    private function __construct(
        array $raw,
        /** Post id. */
        public readonly string $id,
        public readonly ?DateTimeImmutable $from,
        public readonly ?DateTimeImmutable $to,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::requiredStr($data, 'id'), self::date($data, 'from'), self::date($data, 'to'));
    }
}
