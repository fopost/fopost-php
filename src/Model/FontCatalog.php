<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The built-in font library plus a workspace's uploaded fonts. */
final class FontCatalog extends Model
{
    /**
     * @param array<int, LibraryFont> $library
     * @param array<int, UploadedFont> $uploaded
     */
    private function __construct(
        array $raw,
        public readonly array $library,
        public readonly array $uploaded,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            LibraryFont::listFrom(self::seq($data, 'library')),
            UploadedFont::listFrom(self::seq($data, 'uploaded')),
        );
    }
}
