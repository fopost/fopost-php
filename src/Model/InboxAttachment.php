<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Media or a link carried by an inbox item. `url` is served by the API, never a platform URL. */
final class InboxAttachment extends Model
{
    private function __construct(
        array $raw,
        public readonly string $kind,
        public readonly ?string $name,
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly ?string $link,
        public readonly ?string $url,
        public readonly ?string $previewUrl,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'kind'),
            self::str($data, 'name'),
            self::int($data, 'width'),
            self::int($data, 'height'),
            self::str($data, 'link'),
            self::str($data, 'url'),
            self::str($data, 'preview_url'),
        );
    }
}
