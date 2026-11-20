<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** An operation a video edit can run, and whether this deployment serves it. */
final class VideoEditOp extends Model
{
    private function __construct(
        array $raw,
        public readonly string $name,
        public readonly bool $available,
        /** Spends AI credits, so an API key also needs the `ai` scope. */
        public readonly bool $usesAi,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'name'),
            self::bool($data, 'available') ?? false,
            self::bool($data, 'uses_ai') ?? false,
        );
    }
}
