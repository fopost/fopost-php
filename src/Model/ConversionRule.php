<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** How the network attributes a sale or a sign-up back to an ad set. */
final class ConversionRule extends Model
{
    /** @param array<int, string> $campaignIds ad sets this rule is attached to */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $type,
        public readonly ?string $attribution,
        public readonly int $postClickWindowDays,
        public readonly int $viewThroughWindowDays,
        public readonly ?int $valueMinor,
        public readonly ?string $currency,
        public readonly bool $enabled,
        public readonly ?string $createdAt,
        public readonly array $campaignIds,
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
            self::str($data, 'type'),
            self::str($data, 'attribution'),
            self::int($data, 'post_click_window_days') ?? 30,
            self::int($data, 'view_through_window_days') ?? 7,
            self::int($data, 'value_minor'),
            self::str($data, 'currency'),
            self::bool($data, 'enabled') ?? true,
            self::str($data, 'created_at'),
            array_values(array_filter(self::seq($data, 'campaign_ids'), 'is_string')),
        );
    }
}
