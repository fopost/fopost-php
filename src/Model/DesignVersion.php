<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A saved version of a design. `document` is present only when asked for. */
final class DesignVersion extends Model
{
    /** @param array<string, mixed>|null $document */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly int $version,
        public readonly ?array $document,
        public readonly ?DateTimeImmutable $createdAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::int($data, 'version') ?? 0,
            self::nested($data, 'document'),
            self::date($data, 'created_at'),
        );
    }
}
