<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Whether one network took a post down when it was unpublished. */
final class PlatformRemoval extends Model
{
    private function __construct(
        array $raw,
        public readonly string $accountId,
        public readonly string $platform,
        /** False when the network refused to take the post down. */
        public readonly bool $removed,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'account_id'),
            self::requiredStr($data, 'platform'),
            self::bool($data, 'removed') ?? false,
        );
    }
}
