<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A family from the built-in font library. */
final class LibraryFont extends Model
{
    /**
     * @param array<int, string> $scripts
     * @param array<int, int> $weights
     * @param array<int, array<string, mixed>> $files
     */
    private function __construct(
        array $raw,
        public readonly string $slug,
        public readonly string $family,
        public readonly ?string $category,
        public readonly array $scripts,
        public readonly array $weights,
        public readonly array $files,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'slug'),
            self::requiredStr($data, 'family'),
            self::str($data, 'category'),
            array_values(array_filter(self::seq($data, 'scripts'), 'is_string')),
            array_values(array_filter(self::seq($data, 'weights'), 'is_int')),
            array_values(array_filter(self::seq($data, 'files'), 'is_array')),
        );
    }
}
