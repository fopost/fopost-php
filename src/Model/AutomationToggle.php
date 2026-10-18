<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Whether the automation is active after a toggle. */
final class AutomationToggle extends Model
{
    private function __construct(array $raw, public readonly string $id, public readonly bool $active)
    {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::requiredStr($data, 'id'), self::bool($data, 'active') ?? false);
    }
}
