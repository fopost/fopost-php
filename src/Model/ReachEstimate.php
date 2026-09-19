<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The estimated audience size for a targeting spec. `ready` is false while the network is still sizing it. */
final class ReachEstimate extends Model
{
    private function __construct(
        array $raw,
        public readonly ?int $lower,
        public readonly ?int $upper,
        public readonly bool $ready,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'lower'),
            self::int($data, 'upper'),
            self::bool($data, 'ready') ?? false,
        );
    }
}
