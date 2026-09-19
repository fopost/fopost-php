<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The outcome for one object of a bulk status change. Level is campaign, ad_set or ad. */
final class BulkAdStatusResult extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $level,
        public readonly bool $ok,
        public readonly ?string $error,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'level'),
            self::bool($data, 'ok') ?? false,
            self::str($data, 'error'),
        );
    }
}
