<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One run the network made of a product feed. */
final class ProductFeedUpload extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $startedAt,
        public readonly ?string $endedAt,
        public readonly ?string $status,
        public readonly ?int $errorCount,
        public readonly ?int $warningCount,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'started_at'),
            self::str($data, 'ended_at'),
            self::str($data, 'status'),
            self::int($data, 'error_count'),
            self::int($data, 'warning_count'),
        );
    }
}
