<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A node a version added or removed. */
final class AutomationNodeRef extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $kind,
        public readonly ?string $label,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'kind'),
            self::str($data, 'label'),
        );
    }
}
