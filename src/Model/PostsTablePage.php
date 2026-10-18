<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One page of the posts table, with status counts across the whole filtered set. */
final class PostsTablePage extends Model
{
    /**
     * @param array<int, PostsTableRow> $rows
     * @param array<string, int> $summary draft, scheduled, published, failed, pending, total
     */
    private function __construct(
        array $raw,
        public readonly array $rows,
        public readonly PageMeta $meta,
        /** At least 1, so an empty list is one empty page. */
        public readonly int $totalPages,
        public readonly array $summary,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $meta = self::nested($data, 'meta') ?? [];

        return new self(
            $data,
            PostsTableRow::listFrom(self::seq($data, 'data')),
            PageMeta::fromArray($meta),
            self::int($meta, 'total_pages') ?? 1,
            array_map(intval(...), self::map($data, 'summary')),
        );
    }
}
