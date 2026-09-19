<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What POST /validate/post returns: ready only when every platform is. */
final class PostValidation extends Model
{
    /** @param array<int, PostValidationPlatform> $platforms */
    private function __construct(
        array $raw,
        public readonly bool $ready,
        public readonly array $platforms,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::bool($data, 'ready') ?? false,
            PostValidationPlatform::listFrom(self::seq($data, 'platforms')),
        );
    }
}
