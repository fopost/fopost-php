<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What a CSV import did: what it made, what it merged, what it could not read. */
final class ContactImportResult extends Model
{
    /**
     * @param array<int, array<string, mixed>> $skipped
     * @param array<int, string> $unknownColumns
     */
    private function __construct(
        array $raw,
        public readonly int $created,
        /** Rows that folded into a contact already on file. */
        public readonly int $merged,
        public readonly array $skipped,
        /** Columns that named neither a reserved field nor a custom field. */
        public readonly array $unknownColumns,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        /** @var array<int, array<string, mixed>> $skipped */
        $skipped = array_values(array_filter(self::seq($data, 'skipped'), 'is_array'));
        $unknown = array_values(array_filter(self::seq($data, 'unknown_columns'), 'is_string'));

        return new self(
            $data,
            self::int($data, 'created') ?? 0,
            self::int($data, 'merged') ?? 0,
            $skipped,
            $unknown,
        );
    }
}
