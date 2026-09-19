<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A location, interest, behaviour or income bracket as the ad platform names it. */
final class TargetingOption extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $detail,
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
            self::str($data, 'detail'),
        );
    }
}
