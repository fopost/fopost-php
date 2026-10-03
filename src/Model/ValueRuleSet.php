<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Weights conversions so some audiences count for more than others. */
final class ValueRuleSet extends Model
{
    /** @param array<int, array<string, mixed>> $rules */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $status,
        public readonly array $rules,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'name'),
            self::str($data, 'status'),
            self::seq($data, 'rules'),
        );
    }
}
