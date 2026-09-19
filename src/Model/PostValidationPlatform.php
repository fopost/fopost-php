<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One platform's verdict on a draft. */
final class PostValidationPlatform extends Model
{
    /**
     * @param array<int, string> $issues
     * @param array<int, ValidationSignal> $signals
     */
    private function __construct(
        array $raw,
        public readonly string $platform,
        public readonly bool $ready,
        public readonly array $issues,
        public readonly ?float $score,
        public readonly array $signals,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $score = self::field($data, 'score');

        return new self(
            $data,
            self::requiredStr($data, 'platform'),
            self::bool($data, 'ready') ?? false,
            array_values(array_filter(self::seq($data, 'issues'), 'is_string')),
            is_int($score) || is_float($score) ? (float) $score : null,
            ValidationSignal::listFrom(self::seq($data, 'signals')),
        );
    }
}
