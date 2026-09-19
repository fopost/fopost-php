<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One submission of a lead form. `fields` is a list of `{name, values}` rows. */
final class Lead extends Model
{
    /** @param array<int, array<string, mixed>> $fields */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $createdAt,
        public readonly array $fields,
        public readonly ?string $adName,
        public readonly ?string $campaignName,
        public readonly ?string $platform,
        public readonly ?bool $isOrganic,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'created_at'),
            self::seq($data, 'fields'),
            self::str($data, 'ad_name'),
            self::str($data, 'campaign_name'),
            self::str($data, 'platform'),
            self::bool($data, 'is_organic'),
        );
    }
}
