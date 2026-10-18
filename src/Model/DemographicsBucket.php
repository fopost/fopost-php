<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One slice of an audience dimension. */
final class DemographicsBucket extends Model
{
    private function __construct(
        array $raw,
        public readonly string $key,
        public readonly float $value,
        /** Fraction of the dimension total (0-1). */
        public readonly float $share,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'key'),
            self::num($data, 'value') ?? 0.0,
            self::num($data, 'share') ?? 0.0,
        );
    }
}
