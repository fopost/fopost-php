<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What a catalog product batch was accepted as. */
final class CatalogBatchResult extends Model
{
    /** @param array<int, string> $handles */
    private function __construct(
        array $raw,
        public readonly array $handles,
        public readonly int $accepted,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::seq($data, 'handles'), self::int($data, 'accepted') ?? 0);
    }
}
