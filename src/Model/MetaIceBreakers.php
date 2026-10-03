<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The ice breakers set on one account. */
final class MetaIceBreakers extends Model
{
    /** @param array<int, MetaIceBreaker> $iceBreakers */
    private function __construct(
        array $raw,
        public readonly array $iceBreakers,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, MetaIceBreaker::listFrom(self::seq($data, 'ice_breakers')));
    }
}
