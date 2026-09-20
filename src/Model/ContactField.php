<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A column the workspace invented to keep about its contacts. */
final class ContactField extends Model
{
    /** @param array<int, string> $options */
    private function __construct(
        array $raw,
        public readonly string $id,
        /** Lower-case key, also the CSV column header. Fixed once created. */
        public readonly string $key,
        public readonly string $name,
        /** text, number, date, select or boolean. */
        public readonly string $type,
        /** Allowed values when the type is select. */
        public readonly array $options,
        public readonly int $position,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $options = array_values(array_filter(self::seq($data, 'options'), 'is_string'));

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'key'),
            self::requiredStr($data, 'name'),
            self::str($data, 'type') ?? 'text',
            $options,
            self::int($data, 'position') ?? 0,
        );
    }
}
