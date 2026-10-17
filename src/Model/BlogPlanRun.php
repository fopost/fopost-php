<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** The last keyword research run. */
final class BlogPlanRun extends Model
{
    private function __construct(
        array $raw,
        /** queued, running, done or failed. */
        public readonly string $status,
        public readonly ?string $error,
        /** New keywords the run found. */
        public readonly ?int $added,
        public readonly ?DateTimeImmutable $finishedAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'status') ?? 'queued',
            self::str($data, 'error'),
            self::int($data, 'added'),
            self::date($data, 'finishedAt'),
        );
    }
}
