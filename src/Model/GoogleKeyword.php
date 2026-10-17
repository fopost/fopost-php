<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A keyword on an ad group. `id` is `<customerId>~keyword~<adGroupId>~<criterionId>`. */
final class GoogleKeyword extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $adGroupId,
        public readonly string $text,
        public readonly string $matchType,
        public readonly string $status,
        public readonly ?int $cpcBidMinor,
        public readonly ?bool $negative,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'adGroupId'),
            self::requiredStr($data, 'text'),
            self::requiredStr($data, 'matchType'),
            self::requiredStr($data, 'status'),
            self::int($data, 'cpcBidMinor'),
            self::bool($data, 'negative'),
        );
    }
}
