<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What only this network reports, in its own vocabulary. */
final class AccountPlatformMetrics extends Model
{
    private function __construct(
        array $raw,
        public readonly string $platform,
        public readonly PlatformMetricsBlock $account,
        public readonly PlatformMetricsBlock $post,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'platform') ?? '',
            PlatformMetricsBlock::fromArray(self::nested($data, 'account') ?? []),
            PlatformMetricsBlock::fromArray(self::nested($data, 'post') ?? []),
        );
    }
}
