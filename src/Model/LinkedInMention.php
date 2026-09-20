<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** An entity a post can mention; $annotation is what the post text carries. */
final class LinkedInMention extends Model
{
    private function __construct(
        array $raw,
        public readonly string $urn,
        public readonly string $name,
        public readonly ?string $vanityName,
        public readonly ?string $logoUrl,
        public readonly ?string $type,
        public readonly string $annotation,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'urn'),
            self::requiredStr($data, 'name'),
            self::str($data, 'vanity_name'),
            self::str($data, 'logo_url'),
            self::str($data, 'type'),
            self::requiredStr($data, 'annotation'),
        );
    }
}
