<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One CSV row of a bulk import and what the check made of it. */
final class BulkImportRow extends Model
{
    /**
     * @param array{times: int, gap: int, unit: string}|null $repeat
     * @param array<int, string> $accounts
     * @param array<int, string> $errors
     */
    private function __construct(
        array $raw,
        public readonly int $row,
        public readonly ?string $contentPreview,
        public readonly ?string $contentType,
        public readonly ?int $threadParts,
        public readonly ?DateTimeImmutable $scheduleAt,
        public readonly ?array $repeat,
        public readonly array $accounts,
        public readonly int $labels,
        public readonly bool $hasMedia,
        public readonly int $mediaCount,
        public readonly int $captions,
        public readonly array $errors,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'row') ?? 0,
            self::str($data, 'content_preview'),
            self::str($data, 'content_type'),
            self::int($data, 'thread_parts'),
            self::date($data, 'schedule_at'),
            self::nested($data, 'repeat'),
            array_values(array_map(strval(...), self::seq($data, 'accounts'))),
            self::int($data, 'labels') ?? 0,
            self::bool($data, 'has_media') ?? false,
            self::int($data, 'media_count') ?? 0,
            self::int($data, 'captions') ?? 0,
            array_values(array_map(strval(...), self::seq($data, 'errors'))),
        );
    }
}
