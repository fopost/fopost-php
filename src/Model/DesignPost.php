<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A post that used media exported from a design, with its metrics. */
final class DesignPost extends Model
{
    /** @param array<int, string> $platforms */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $status,
        public readonly ?string $title,
        public readonly ?string $text,
        public readonly ?DateTimeImmutable $scheduleAt,
        public readonly ?DateTimeImmutable $publishedAt,
        public readonly array $platforms,
        public readonly ?int $engagement,
        public readonly ?int $views,
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
            self::str($data, 'title'),
            self::str($data, 'text'),
            self::date($data, 'schedule_at'),
            self::date($data, 'published_at'),
            array_values(array_filter(self::seq($data, 'platforms'), 'is_string')),
            self::int($data, 'engagement'),
            self::int($data, 'views'),
        );
    }
}
