<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A creative in an ad account's library. Format is image, video, carousel, post or other. */
final class AdCreative extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly string $format,
        public readonly ?string $status,
        public readonly ?string $title,
        public readonly ?string $body,
        public readonly ?string $link,
        public readonly ?string $thumbnailUrl,
        public readonly ?string $callToAction,
        public readonly ?string $urlTags,
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
            self::requiredStr($data, 'format'),
            self::str($data, 'status'),
            self::str($data, 'title'),
            self::str($data, 'body'),
            self::str($data, 'link'),
            self::str($data, 'thumbnail_url'),
            self::str($data, 'call_to_action'),
            self::str($data, 'url_tags'),
        );
    }
}
