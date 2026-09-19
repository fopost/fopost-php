<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** $status is pending, connected, failed or expired; $reason is set when failed. */
final class TelegramConnectStatus extends Model
{
    private function __construct(
        array $raw,
        public readonly string $status,
        public readonly ?string $accountId,
        public readonly ?string $reason,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'status'),
            self::str($data, 'account_id'),
            self::str($data, 'reason'),
        );
    }
}
