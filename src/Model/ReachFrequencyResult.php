<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The predictions on one ad account. */
final class ReachFrequencyResult extends Model
{
    /** @param array<int, ReachFrequencyPrediction> $predictions */
    private function __construct(
        array $raw,
        public readonly array $predictions,
        public readonly ?string $workspaceId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            ReachFrequencyPrediction::listFrom(self::seq($data, 'predictions')),
            self::str($data, 'workspace_id'),
        );
    }
}
