<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The collected profile readings for one account, newest first. */
final class AccountAnalyticsHistory extends Model
{
    /** @param array<int, AccountSnapshot> $history */
    private function __construct(
        array $raw,
        public readonly string $accountId,
        public readonly string $platform,
        public readonly string $username,
        public readonly array $history,
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
            self::requiredStr($data, 'username'),
            AccountSnapshot::listFrom(self::seq($data, 'history')),
        );
    }
}
