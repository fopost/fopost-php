<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What this account has published in the rolling window, and what is left. */
final class InstagramPublishingLimit extends Model
{
    private function __construct(
        array $raw,
        public readonly int $quotaUsage,
        public readonly ?int $quotaTotal,
        public readonly ?int $quotaDurationSec,
        public readonly ?int $remaining,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'quota_usage') ?? 0,
            self::int($data, 'quota_total'),
            self::int($data, 'quota_duration_sec'),
            self::int($data, 'remaining'),
        );
    }
}
