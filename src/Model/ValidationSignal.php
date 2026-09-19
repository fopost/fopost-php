<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** An advisory note on a validation result. Never blocks publishing. */
final class ValidationSignal extends Model
{
    private function __construct(
        array $raw,
        public readonly string $level,
        public readonly string $code,
        public readonly string $message,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'level'),
            self::requiredStr($data, 'code'),
            self::requiredStr($data, 'message'),
        );
    }
}
