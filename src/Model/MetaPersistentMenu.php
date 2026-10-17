<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The persistent menu set on one account, one entry per locale. */
final class MetaPersistentMenu extends Model
{
    /** @param array<int, MetaPersistentMenuEntry> $persistentMenu */
    private function __construct(
        array $raw,
        public readonly array $persistentMenu,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            MetaPersistentMenuEntry::listFrom(self::seq($data, 'persistent_menu')),
        );
    }
}
