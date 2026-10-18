<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A pixel as the list answers it. */
final class AdPixel extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::requiredStr($data, 'id'), self::requiredStr($data, 'name'));
    }
}
