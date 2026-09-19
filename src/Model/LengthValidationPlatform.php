<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** How one platform counts a text against its limit. */
final class LengthValidationPlatform extends Model
{
    /** @param array<int, ValidationSignal> $signals */
    private function __construct(
        array $raw,
        public readonly string $platform,
        public readonly int $length,
        public readonly ?int $limit,
        public readonly string $unit,
        public readonly bool $ok,
        public readonly array $signals,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'platform'),
            self::int($data, 'length') ?? 0,
            self::int($data, 'limit'),
            self::str($data, 'unit') ?? 'chars',
            self::bool($data, 'ok') ?? false,
            ValidationSignal::listFrom(self::seq($data, 'signals')),
        );
    }
}
