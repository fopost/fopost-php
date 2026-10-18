<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What a committed bulk import created. */
final class BulkImportCommit extends Model
{
    /** @param array<int, BulkImportPost> $posts */
    private function __construct(
        array $raw,
        public readonly string $batchId,
        public readonly int $created,
        /** True when the workspace requires approval and the posts are held. */
        public readonly bool $heldForApproval,
        public readonly array $posts,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'batch_id'),
            self::int($data, 'created') ?? 0,
            self::bool($data, 'held_for_approval') ?? false,
            BulkImportPost::listFrom(self::seq($data, 'posts')),
        );
    }
}
