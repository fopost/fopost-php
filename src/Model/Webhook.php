<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** An endpoint FoPost sends events to. */
final class Webhook extends Model
{
    /**
     * @param array<int, string> $events
     * @param array<int, string> $headerNames Custom header names; the values are write-only.
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $workspaceId,
        public readonly ?string $name,
        public readonly string $url,
        public readonly array $events,
        public readonly array $headerNames,
        public readonly bool $active,
        /** The signing secret. Present on creation and on a rotation, and only then. */
        public readonly ?string $secret,
        public readonly ?DateTimeImmutable $lastTriggeredAt,
        public readonly int $failureCount,
        public readonly ?DateTimeImmutable $createdAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'workspace_id'),
            self::str($data, 'name'),
            self::requiredStr($data, 'url'),
            array_values(array_map(strval(...), self::seq($data, 'events'))),
            array_values(array_map(strval(...), self::seq($data, 'header_names'))),
            self::bool($data, 'active') ?? false,
            self::str($data, 'secret'),
            self::date($data, 'last_triggered_at'),
            self::int($data, 'failure_count') ?? 0,
            self::date($data, 'created_at'),
        );
    }
}
