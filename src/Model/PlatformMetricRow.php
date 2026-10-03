<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One metric a network reports under its own name. */
final class PlatformMetricRow extends Model
{
    private function __construct(
        array $raw,
        /** The platform's own metric name. Stable — read this, not $label. */
        public readonly string $key,
        /** Ours, and subject to rewording. */
        public readonly string $label,
        /** count | duration_ms | currency_usd | ratio | series */
        public readonly string $kind,
        /** A number for every kind but `series`, which is a list of points. */
        public readonly mixed $value,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'key'),
            self::str($data, 'label') ?? '',
            self::str($data, 'kind') ?? 'count',
            self::field($data, 'value'),
        );
    }
}
