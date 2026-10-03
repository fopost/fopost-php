<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Who did it: `user`, `api_key`, `agent` or `system`. `name` is absent for a system event. */
final class ActivityActor extends Model
{
    private function __construct(
        array $raw,
        public readonly string $type,
        public readonly ?string $name,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::requiredStr($data, 'type'), self::str($data, 'name'));
    }
}
