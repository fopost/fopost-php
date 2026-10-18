<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What a live credential check found for one account. */
final class AccountValidation extends Model
{
    private function __construct(
        array $raw,
        public readonly string $accountId,
        public readonly string $platform,
        public readonly bool $valid,
        /** healthy or expired. */
        public readonly string $healthStatus,
        public readonly ?string $username,
        public readonly ?string $name,
        public readonly ?string $avatar,
        /** `extension` for a manual platform that publishes through the browser extension. */
        public readonly ?string $delivery,
        /** For a manual platform: how delivery works on that network. */
        public readonly ?string $message,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'account_id'),
            self::requiredStr($data, 'platform'),
            self::bool($data, 'valid') ?? false,
            self::requiredStr($data, 'health_status'),
            self::str($data, 'username'),
            self::str($data, 'name'),
            self::str($data, 'avatar'),
            self::str($data, 'delivery'),
            self::str($data, 'message'),
        );
    }
}
