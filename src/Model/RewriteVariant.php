<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One platform's rewritten copy. */
final class RewriteVariant extends Model
{
    private function __construct(
        array $raw,
        public readonly string $platform,
        public readonly string $content,
        public readonly ?int $credits,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'platform'),
            self::requiredStr($data, 'content'),
            self::int($data, 'credits'),
        );
    }
}
