<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The files an export rendered into the media library. */
final class DesignExport extends Model
{
    /** @param array<int, DesignExportFile> $files */
    private function __construct(
        array $raw,
        public readonly string $format,
        public readonly array $files,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'format'),
            DesignExportFile::listFrom(self::seq($data, 'files')),
        );
    }
}
