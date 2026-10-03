<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A persistent-menu item: a `postback` with a payload, or a `web_url` with a link. */
final class MetaMenuItem extends Model
{
    private function __construct(
        array $raw,
        public readonly string $type,
        public readonly string $title,
        public readonly ?string $payload,
        public readonly ?string $url,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'type'),
            self::requiredStr($data, 'title'),
            self::str($data, 'payload'),
            self::str($data, 'url'),
        );
    }
}
