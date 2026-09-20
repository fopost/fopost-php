<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The change log of one ad account. */
final class AdActivityResult extends Model
{
    /** @param array<int, AdActivity> $activity */
    private function __construct(
        array $raw,
        public readonly array $activity,
        public readonly ?string $workspaceId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            AdActivity::listFrom(self::seq($data, 'activity')),
            self::str($data, 'workspace_id'),
        );
    }
}
