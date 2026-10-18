<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The latest profile numbers of every account in a workspace, and their sum. */
final class WorkspaceAnalytics extends Model
{
    /** @param array<int, WorkspaceAccountStats> $accounts */
    private function __construct(
        array $raw,
        public readonly string $workspaceId,
        public readonly array $accounts,
        public readonly int $totalFollowers,
        public readonly int $totalPosts,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $totals = self::map($data, 'totals');

        return new self(
            $data,
            self::requiredStr($data, 'workspace_id'),
            WorkspaceAccountStats::listFrom(self::seq($data, 'accounts')),
            self::int($totals, 'followers') ?? 0,
            self::int($totals, 'total_posts') ?? 0,
        );
    }
}
