<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The account an ad runs as. Meta calls it a Page, TikTok an identity. */
final class AdIdentity extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        /** The network's own identity kind, e.g. CUSTOMIZED_USER. */
        public readonly string $type,
        public readonly string $name,
        public readonly ?string $avatarUrl,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'type'),
            self::requiredStr($data, 'name'),
            self::str($data, 'avatar_url'),
        );
    }
}
