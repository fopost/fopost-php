<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One row of an insights report: a breakdown `key` or a timeline `date`, with its metrics. */
final class InsightsRow extends Model
{
    private function __construct(
        array $raw,
        public readonly ?string $key,
        public readonly ?string $date,
        public readonly InsightsMetrics $metrics,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'key'),
            self::str($data, 'date'),
            InsightsMetrics::fromArray(self::nested($data, 'metrics')),
        );
    }
}
