<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What POST /posts/{id}/analytics/collect returns. */
final class CollectPostResult extends Model
{
    /** @param array<int, CollectPostDelivery> $deliveries */
    private function __construct(
        array $raw,
        public readonly int $collected,
        public readonly array $deliveries,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'collected') ?? 0,
            CollectPostDelivery::listFrom(self::seq($data, 'deliveries')),
        );
    }
}
