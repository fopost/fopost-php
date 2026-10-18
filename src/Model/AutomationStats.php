<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Automation counts across every workspace the key can reach. */
final class AutomationStats extends Model
{
    /**
     * @param array<string, int> $runsByStatus
     * @param array<int, AutomationRun> $recentRuns
     */
    private function __construct(
        array $raw,
        public readonly int $totalAutomations,
        public readonly int $activeAutomations,
        /** Runs started in the last 24 hours. */
        public readonly int $totalRuns24h,
        public readonly array $runsByStatus,
        public readonly array $recentRuns,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'total_automations') ?? 0,
            self::int($data, 'active_automations') ?? 0,
            self::int($data, 'total_runs_24h') ?? 0,
            array_map(intval(...), self::map($data, 'runs_by_status')),
            AutomationRun::listFrom(self::seq($data, 'recent_runs')),
        );
    }
}
