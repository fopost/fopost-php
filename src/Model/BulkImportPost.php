<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A post a bulk import created. */
final class BulkImportPost extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?DateTimeImmutable $scheduleAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::requiredStr($data, 'id'), self::date($data, 'schedule_at'));
    }
}
