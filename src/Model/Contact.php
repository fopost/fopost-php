<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One person, however many handles they write from. */
final class Contact extends Model
{
    /**
     * @param array<int, ContactChannel> $channels
     * @param array<string, mixed> $fields
     * @param array<int, array<string, mixed>> $labels
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $displayName,
        public readonly array $channels,
        /** inbox, radar or import — what first created the row. */
        public readonly string $source,
        public readonly ?string $note,
        public readonly ?DateTimeImmutable $firstSeenAt,
        public readonly ?DateTimeImmutable $lastSeenAt,
        /** Custom field values, keyed by field key. */
        public readonly array $fields,
        public readonly array $labels,
        /** Only on a listing that spans workspaces. */
        public readonly ?string $workspaceId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        /** @var array<int, array<string, mixed>> $labels */
        $labels = array_values(array_filter(self::seq($data, 'labels'), 'is_array'));

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'display_name'),
            ContactChannel::listFrom(self::seq($data, 'channels')),
            self::str($data, 'source') ?? 'inbox',
            self::str($data, 'note'),
            self::date($data, 'first_seen_at'),
            self::date($data, 'last_seen_at'),
            self::map($data, 'fields'),
            $labels,
            self::str($data, 'workspace_id'),
        );
    }
}
