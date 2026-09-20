<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One thing that happened in a workspace. `kind` of `security` is an audit row. */
final class ActivityEvent extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $workspaceId,
        public readonly string $kind,
        public readonly ?string $refType,
        public readonly ?string $refId,
        public readonly string $summary,
        public readonly ActivityActor $actor,
        public readonly ?DateTimeImmutable $time,
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
            self::requiredStr($data, 'kind'),
            self::str($data, 'ref_type'),
            self::str($data, 'ref_id'),
            self::requiredStr($data, 'summary'),
            ActivityActor::fromArray(self::nested($data, 'actor') ?? []),
            self::date($data, 'time'),
        );
    }
}
