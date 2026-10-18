<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** What one step of a run did. */
final class AutomationRunLog extends Model
{
    /**
     * @param array<string, mixed>|null $inputSnapshot
     * @param array<string, mixed>|null $outputSnapshot
     */
    private function __construct(
        array $raw,
        public readonly int $id,
        public readonly int $stepPosition,
        public readonly ?string $nodeId,
        /** The output the run left the node through. */
        public readonly ?string $handle,
        public readonly string $status,
        public readonly ?array $inputSnapshot,
        public readonly ?array $outputSnapshot,
        public readonly ?DateTimeImmutable $startedAt,
        public readonly ?DateTimeImmutable $completedAt,
        public readonly ?int $durationMs,
        public readonly ?string $errorMessage,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'id') ?? 0,
            self::int($data, 'step_position') ?? 0,
            self::str($data, 'node_id'),
            self::str($data, 'handle'),
            self::requiredStr($data, 'status'),
            self::nested($data, 'input_snapshot'),
            self::nested($data, 'output_snapshot'),
            self::date($data, 'started_at'),
            self::date($data, 'completed_at'),
            self::int($data, 'duration_ms'),
            self::str($data, 'error_message'),
        );
    }
}
