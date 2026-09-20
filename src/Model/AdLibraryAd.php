<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A public ad from the network's own library, never a connection's own data. */
final class AdLibraryAd extends Model
{
    /** @param array<int, string> $countries */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $advertiserName,
        public readonly ?string $advertiserUrl,
        public readonly ?string $headline,
        public readonly ?string $body,
        public readonly ?string $type,
        public readonly ?string $thumbnailUrl,
        public readonly ?string $firstImpressionAt,
        public readonly ?string $lastImpressionAt,
        public readonly array $countries,
        public readonly ?string $detailsUrl,
        public readonly ?string $payer,
        public readonly ?string $impressionsRange,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'advertiser_name'),
            self::str($data, 'advertiser_url'),
            self::str($data, 'headline'),
            self::str($data, 'body'),
            self::str($data, 'type'),
            self::str($data, 'thumbnail_url'),
            self::str($data, 'first_impression_at'),
            self::str($data, 'last_impression_at'),
            array_values(array_filter(self::seq($data, 'countries'), 'is_string')),
            self::str($data, 'details_url'),
            self::str($data, 'payer'),
            self::str($data, 'impressions_range'),
        );
    }
}
