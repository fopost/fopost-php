<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A creator who allowlisted this advertiser for partnership ads. */
final class PartnershipCreator extends Model
{
    /** @param array<int, string> $permissions */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $username,
        public readonly ?string $name,
        public readonly ?string $status,
        public readonly array $permissions,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'username'),
            self::str($data, 'name'),
            self::str($data, 'status'),
            self::seq($data, 'permissions'),
        );
    }
}
