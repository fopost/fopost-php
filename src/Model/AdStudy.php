<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** An A/B study splitting traffic across its cells. */
final class AdStudy extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $type,
        public readonly ?string $status,
        public readonly ?string $startAt,
        public readonly ?string $endAt,
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
            self::str($data, 'description'),
            self::str($data, 'type'),
            self::str($data, 'status'),
            self::str($data, 'start_at'),
            self::str($data, 'end_at'),
        );
    }
}
