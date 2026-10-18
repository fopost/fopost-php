<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A trigger plus the chain of steps it runs. */
final class Automation extends Model
{
    /**
     * @param array<string, mixed> $triggerConfig
     * @param array{days: array<int, int>, start: string, end: string}|null $scheduleWindow
     * @param array<string, int>|null $maxPerDay
     * @param array{nodes: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>}|null $graph
     * @param array<int, AutomationStep> $steps
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $workspaceId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $triggerType,
        public readonly array $triggerConfig,
        public readonly bool $active,
        /** Wall-clock window (workspace timezone) runs may act in; null is unrestricted. */
        public readonly ?array $scheduleWindow,
        /** Daily caps; null is unrestricted. */
        public readonly ?array $maxPerDay,
        /** Max AI credits one unattended run may spend. */
        public readonly ?int $creditCeiling,
        /** Generated posts go through the approval queue. */
        public readonly bool $approvalMode,
        /** The webhook trigger secret. Returned on creation only. */
        public readonly ?string $secret,
        public readonly ?array $graph,
        public readonly array $steps,
        public readonly ?DateTimeImmutable $lastTriggeredAt,
        public readonly int $runCount,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt,
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
            self::requiredStr($data, 'name'),
            self::str($data, 'description'),
            self::requiredStr($data, 'trigger_type'),
            self::map($data, 'trigger_config'),
            self::bool($data, 'active') ?? false,
            self::nested($data, 'schedule_window'),
            self::nested($data, 'max_per_day'),
            self::int($data, 'credit_ceiling'),
            self::bool($data, 'approval_mode') ?? false,
            self::str($data, 'secret'),
            self::nested($data, 'graph'),
            AutomationStep::listFrom(self::seq($data, 'steps')),
            self::date($data, 'last_triggered_at'),
            self::int($data, 'run_count') ?? 0,
            self::date($data, 'created_at'),
            self::date($data, 'updated_at'),
        );
    }
}
