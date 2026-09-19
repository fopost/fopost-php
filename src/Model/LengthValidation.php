<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What POST /validate/length returns. */
final class LengthValidation extends Model
{
    /** @param array<int, LengthValidationPlatform> $platforms */
    private function __construct(
        array $raw,
        public readonly bool $ok,
        public readonly array $platforms,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::bool($data, 'ok') ?? false,
            LengthValidationPlatform::listFrom(self::seq($data, 'platforms')),
        );
    }
}
