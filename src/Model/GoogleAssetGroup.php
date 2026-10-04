<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A Performance Max asset group. */
final class GoogleAssetGroup extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $campaignId,
        public readonly string $name,
        public readonly string $status,
        /** @var array<int, string> */
        public readonly array $finalUrls,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'campaignId'),
            self::requiredStr($data, 'name'),
            self::requiredStr($data, 'status'),
            array_values(array_filter(self::seq($data, 'finalUrls'), 'is_string')),
        );
    }
}
