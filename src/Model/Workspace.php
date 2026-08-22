<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A workspace the API key can reach. */
final class Workspace extends Model
{
    /** @param array<int, SocialAccount> $accounts */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $slug,
        public readonly ?string $type,
        public readonly ?string $logo,
        public readonly ?string $website,
        public readonly ?string $timezone,
        public readonly ?string $country,
        public readonly ?string $description,
        public readonly ?string $language,
        public readonly ?bool $requireApproval,
        public readonly ?bool $aiAltTextEnabled,
        public readonly ?string $brandColor,
        public readonly ?string $role,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly array $accounts,
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
            self::str($data, 'slug'),
            self::str($data, 'type'),
            self::str($data, 'logo'),
            self::str($data, 'website'),
            self::str($data, 'timezone'),
            self::str($data, 'country'),
            self::str($data, 'description'),
            self::str($data, 'language'),
            self::bool($data, 'require_approval'),
            self::bool($data, 'ai_alt_text_enabled'),
            self::str($data, 'brand_color'),
            self::str($data, 'role'),
            self::date($data, 'created_at'),
            SocialAccount::listFrom(self::seq($data, 'accounts')),
        );
    }
}
