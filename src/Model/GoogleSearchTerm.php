<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What someone actually searched, with the metrics it earned. */
final class GoogleSearchTerm extends Model
{
    private function __construct(
        array $raw,
        public readonly string $term,
        public readonly ?string $adGroupId,
        public readonly ?string $status,
        /** @var array<string, mixed> */
        public readonly array $metrics,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'term'),
            self::str($data, 'adGroupId'),
            self::str($data, 'status'),
            self::map($data, 'metrics'),
        );
    }
}
