<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The row-by-row check of a bulk import CSV, before anything is created. */
final class BulkImportValidation extends Model
{
    /** @param array<int, BulkImportRow> $rows */
    private function __construct(
        array $raw,
        public readonly int $totalRows,
        public readonly int $validRows,
        public readonly int $invalidRows,
        /** The most data rows one import accepts. */
        public readonly int $maxRows,
        public readonly array $rows,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'total_rows') ?? 0,
            self::int($data, 'valid_rows') ?? 0,
            self::int($data, 'invalid_rows') ?? 0,
            self::int($data, 'max_rows') ?? 0,
            BulkImportRow::listFrom(self::seq($data, 'rows')),
        );
    }
}
