<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The pagination block a list endpoint returns beside its data. */
final class PageMeta extends Model
{
    private function __construct(
        array $raw,
        public readonly ?int $currentPage,
        public readonly ?int $perPage,
        public readonly ?int $total,
        public readonly ?int $lastPage,
        public readonly ?int $from,
        public readonly ?int $to,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'current_page'),
            self::int($data, 'per_page'),
            self::int($data, 'total'),
            self::int($data, 'last_page'),
            self::int($data, 'from'),
            self::int($data, 'to'),
        );
    }

    public static function empty(): self
    {
        return self::fromArray([]);
    }
}
