<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A named set of connected accounts in one workspace. */
final class AccountGroup extends Model
{
    /** @param array<int, string> $accountIds */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly array $accountIds,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt,
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
            array_values(array_filter(self::seq($data, 'account_ids'), 'is_string')),
            self::date($data, 'created_at'),
            self::date($data, 'updated_at'),
        );
    }
}
