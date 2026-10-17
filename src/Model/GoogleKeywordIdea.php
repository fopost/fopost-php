<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A keyword idea or its historical metrics. */
final class GoogleKeywordIdea extends Model
{
    private function __construct(
        array $raw,
        public readonly string $text,
        public readonly ?int $avgMonthlySearches,
        public readonly ?string $competition,
        public readonly ?int $lowTopOfPageBidMinor,
        public readonly ?int $highTopOfPageBidMinor,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'text'),
            self::int($data, 'avgMonthlySearches'),
            self::str($data, 'competition'),
            self::int($data, 'lowTopOfPageBidMinor'),
            self::int($data, 'highTopOfPageBidMinor'),
        );
    }
}
