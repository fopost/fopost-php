<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A search term the blog plans to write about. */
final class BlogKeyword extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $keyword,
        public readonly ?int $searchVolume,
        /** 0 to 100. */
        public readonly ?int $difficulty,
        public readonly ?float $cpc,
        public readonly ?string $intent,
        /** provider, user or agent. */
        public readonly string $source,
        /** suggested, planned, written or dismissed. */
        public readonly string $status,
        /** YYYY-MM-DD. */
        public readonly ?string $plannedFor,
        public readonly ?DateTimeImmutable $createdAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'keyword'),
            self::int($data, 'searchVolume'),
            self::int($data, 'difficulty'),
            self::num($data, 'cpc'),
            self::str($data, 'intent'),
            self::str($data, 'source') ?? 'user',
            self::str($data, 'status') ?? 'suggested',
            self::str($data, 'plannedFor'),
            self::date($data, 'createdAt'),
        );
    }
}
