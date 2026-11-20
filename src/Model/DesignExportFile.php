<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One rendered page of an export, stored in the media library. */
final class DesignExportFile extends Model
{
    private function __construct(
        array $raw,
        public readonly string $mediaId,
        public readonly ?int $pageIndex,
        public readonly ?string $mimeType,
        public readonly ?string $previewUrl,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'media_id'),
            self::int($data, 'page_index'),
            self::str($data, 'mime_type'),
            self::str($data, 'preview_url'),
        );
    }
}
