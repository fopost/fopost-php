<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A sitelink, callout or structured snippet. */
final class GoogleAsset extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $name,
        public readonly string $type,
        public readonly ?string $text,
        public readonly ?string $finalUrl,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'name'),
            self::requiredStr($data, 'type'),
            self::str($data, 'text'),
            self::str($data, 'finalUrl'),
        );
    }
}
