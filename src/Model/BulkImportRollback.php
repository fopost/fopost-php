<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** How many posts a rolled-back bulk import deleted. */
final class BulkImportRollback extends Model
{
    private function __construct(array $raw, public readonly int $deleted)
    {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::int($data, 'deleted') ?? 0);
    }
}
