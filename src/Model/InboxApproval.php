<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A drafted reply a person still has to send. */
final class InboxApproval extends Model
{
    /** @param array<string, mixed>|null $item */
    private function __construct(
        array $raw,
        public readonly int $id,
        public readonly ?string $workspaceId,
        public readonly ?string $source,
        public readonly string $reply,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?array $item,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'id') ?? 0,
            self::str($data, 'workspace_id'),
            self::str($data, 'source'),
            self::requiredStr($data, 'reply'),
            self::date($data, 'created_at'),
            self::nested($data, 'item'),
        );
    }
}
