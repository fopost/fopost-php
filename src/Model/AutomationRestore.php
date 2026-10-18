<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The new version a restore created. */
final class AutomationRestore extends Model
{
    /**
     * @param array{nodes: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>}|null $graph
     * @param array<int, AutomationStep> $steps
     */
    private function __construct(
        array $raw,
        public readonly int $version,
        public readonly ?array $graph,
        public readonly array $steps,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'version') ?? 0,
            self::nested($data, 'graph'),
            AutomationStep::listFrom(self::seq($data, 'steps')),
        );
    }
}
