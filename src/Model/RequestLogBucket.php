<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** Request and error counts for one time bucket. */
final class RequestLogBucket extends Model
{
    private function __construct(
        array $raw,
        public readonly ?DateTimeImmutable $at,
        public readonly int $total,
        public readonly int $errors,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::date($data, 'at'),
            self::int($data, 'total') ?? 0,
            self::int($data, 'errors') ?? 0,
        );
    }
}
