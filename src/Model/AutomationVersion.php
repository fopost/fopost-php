<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A saved version of an automation's graph. The graph itself travels only on the single read. */
final class AutomationVersion extends Model
{
    /**
     * @param array<int, AutomationNodeRef> $nodesAdded
     * @param array<int, AutomationNodeRef> $nodesRemoved
     * @param array{nodes: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>}|null $graph
     */
    private function __construct(
        array $raw,
        public readonly int $version,
        public readonly ?string $note,
        public readonly ?string $createdByName,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?int $nodeCount,
        public readonly array $nodesAdded,
        public readonly array $nodesRemoved,
        public readonly ?array $graph,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $createdBy = self::nested($data, 'created_by') ?? [];

        return new self(
            $data,
            self::int($data, 'version') ?? 0,
            self::str($data, 'note'),
            self::str($createdBy, 'name'),
            self::date($data, 'created_at'),
            self::int($data, 'node_count'),
            AutomationNodeRef::listFrom(self::seq($data, 'nodes_added')),
            AutomationNodeRef::listFrom(self::seq($data, 'nodes_removed')),
            self::nested($data, 'graph'),
        );
    }
}
