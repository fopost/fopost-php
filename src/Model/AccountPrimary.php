<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Whether the account is the workspace's primary one after a toggle. */
final class AccountPrimary extends Model
{
    private function __construct(array $raw, public readonly string $id, public readonly bool $isPrimary)
    {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::requiredStr($data, 'id'), self::bool($data, 'is_primary') ?? false);
    }
}
