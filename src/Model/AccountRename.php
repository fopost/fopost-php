<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The result of renaming an account; $name is the override when set, else the platform name. */
final class AccountRename extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $name,
        public readonly ?string $platformName,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'name'),
            self::str($data, 'platform_name'),
        );
    }
}
