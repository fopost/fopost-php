<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** An Instant Form on a Page. */
final class LeadForm extends Model
{
    /** @param array<int, string> $questions */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $status,
        public readonly int $leadsCount,
        public readonly ?string $createdAt,
        public readonly array $questions,
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
            self::str($data, 'status'),
            self::int($data, 'leads_count') ?? 0,
            self::str($data, 'created_at'),
            self::seq($data, 'questions'),
        );
    }
}
