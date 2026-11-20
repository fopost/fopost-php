<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A queued video edit job. Poll it until `status` is succeeded or failed. */
final class VideoEdit extends Model
{
    /**
     * @param array<int, array{op: string, params?: array<string, mixed>}> $operations
     * @param array{id?: string, preview_url?: ?string, mime_type?: ?string}|null $resultMedia
     * @param array<int, array<string, mixed>> $extraOutputs Additional files, such as clips or subtitles.
     * @param array<string, mixed>|null $result
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        /** queued, running, succeeded or failed. */
        public readonly string $status,
        public readonly ?float $progress,
        public readonly ?string $sourceMediaId,
        public readonly array $operations,
        public readonly ?string $resultMediaId,
        public readonly ?array $resultMedia,
        public readonly array $extraOutputs,
        public readonly ?array $result,
        public readonly ?string $error,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt,
        public readonly ?DateTimeImmutable $completedAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'status'),
            self::num($data, 'progress'),
            self::str($data, 'source_media_id'),
            array_values(array_filter(self::seq($data, 'operations'), 'is_array')),
            self::str($data, 'result_media_id'),
            self::nested($data, 'result_media'),
            array_values(array_filter(self::seq($data, 'extra_outputs'), 'is_array')),
            self::nested($data, 'result'),
            self::str($data, 'error'),
            self::date($data, 'created_at'),
            self::date($data, 'updated_at'),
            self::date($data, 'completed_at'),
        );
    }
}
