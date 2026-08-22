<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Credits charged by one AI call, and what is left afterwards. */
final class AiCredits extends Model
{
    private function __construct(
        array $raw,
        public readonly int $charged,
        public readonly int $remaining,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::int($data, 'charged') ?? 0, self::int($data, 'remaining') ?? 0);
    }
}
