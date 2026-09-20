<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One slot of a campaign's ad schedule. */
final class GoogleAdScheduleSlot extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $dayOfWeek,
        public readonly ?int $startHour,
        public readonly ?int $endHour,
        public readonly ?float $bidModifier,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'dayOfWeek'),
            self::int($data, 'startHour'),
            self::int($data, 'endHour'),
            is_numeric(self::field($data, 'bidModifier')) ? (float) self::field($data, 'bidModifier') : null,
        );
    }
}
