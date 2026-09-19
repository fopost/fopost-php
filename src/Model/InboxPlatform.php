<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Inbox support per platform: `comments` and `dms` are `live`, `soon` or `none`. */
final class InboxPlatform extends Model
{
    private function __construct(
        array $raw,
        public readonly string $platform,
        public readonly string $comments,
        public readonly string $dms,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'platform'),
            self::requiredStr($data, 'comments'),
            self::requiredStr($data, 'dms'),
        );
    }
}
