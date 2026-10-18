<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One execution of an automation. The step logs travel only on the single read. */
final class AutomationRun extends Model
{
    /**
     * @param array<string, mixed>|null $triggerEvent
     * @param array<string, mixed>|null $context
     * @param array<string, mixed>|null $initialContext
     * @param array<int, AutomationRunLog> $logs
     */
    private function __construct(
        array $raw,
        public readonly int $id,
        public readonly ?string $automationId,
        /** running, deferred, waiting, completed, failed or cancelled. */
        public readonly string $status,
        public readonly ?int $currentStep,
        public readonly ?string $currentNodeId,
        /** When a waiting run gives up on the reply. */
        public readonly ?DateTimeImmutable $waitingUntil,
        public readonly ?array $triggerEvent,
        public readonly ?array $context,
        /** The context before the first step; what a replay re-runs against. */
        public readonly ?array $initialContext,
        public readonly ?int $replayOfRunId,
        public readonly ?DateTimeImmutable $deferredUntil,
        /** `outside_window` or `daily_cap`. */
        public readonly ?string $deferReason,
        public readonly ?string $deferDetail,
        public readonly int $deferCount,
        public readonly ?DateTimeImmutable $startedAt,
        public readonly ?DateTimeImmutable $completedAt,
        public readonly ?string $errorMessage,
        public readonly array $logs,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'id') ?? 0,
            self::str($data, 'automation_id'),
            self::requiredStr($data, 'status'),
            self::int($data, 'current_step'),
            self::str($data, 'current_node_id'),
            self::date($data, 'waiting_until'),
            self::nested($data, 'trigger_event'),
            self::nested($data, 'context'),
            self::nested($data, 'initial_context'),
            self::int($data, 'replay_of_run_id'),
            self::date($data, 'deferred_until'),
            self::str($data, 'defer_reason'),
            self::str($data, 'defer_detail'),
            self::int($data, 'defer_count') ?? 0,
            self::date($data, 'started_at'),
            self::date($data, 'completed_at'),
            self::str($data, 'error_message'),
            AutomationRunLog::listFrom(self::seq($data, 'logs')),
        );
    }
}
