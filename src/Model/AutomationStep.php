<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One non-trigger node of an automation, in stored order. */
final class AutomationStep extends Model
{
    /** @param array<string, mixed> $actionConfig */
    private function __construct(
        array $raw,
        public readonly int $position,
        public readonly ?string $nodeId,
        public readonly string $actionType,
        public readonly array $actionConfig,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'position') ?? 0,
            self::str($data, 'node_id'),
            self::requiredStr($data, 'action_type'),
            self::map($data, 'action_config'),
        );
    }
}
