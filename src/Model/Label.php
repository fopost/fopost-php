<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A workspace label you can attach to posts. */
final class Label extends Model
{
    /** @param array<string, mixed>|null $workspace */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $color,
        public readonly ?array $workspace,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'name'),
            self::str($data, 'color'),
            self::nested($data, 'workspace'),
        );
    }
}
