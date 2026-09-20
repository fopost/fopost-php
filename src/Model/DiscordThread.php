<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A thread started on a message. */
final class DiscordThread extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $parentId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'name') ?? '',
            self::str($data, 'parent_id'),
        );
    }
}
