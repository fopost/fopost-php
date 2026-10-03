<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One side of a per-network metric set: the account itself, or its newest measured post. */
final class PlatformMetricsBlock extends Model
{
    private function __construct(
        array $raw,
        public readonly ?string $fetchedAt,
        /** Null on the account block, and on a network that reports nothing per post. */
        public readonly ?string $externalPostId,
        /** @var array<int, PlatformMetricRow> */
        public readonly array $metrics,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'fetched_at'),
            self::str($data, 'external_post_id'),
            PlatformMetricRow::listFrom(self::seq($data, 'metrics')),
        );
    }
}
