<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A published post with the deliveries a boost can be built from. */
final class BoostablePost extends Model
{
    /** @param array<int, array<string, mixed>> $deliveries */
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $workspaceId,
        public readonly ?string $text,
        public readonly ?string $thumbnailUrl,
        public readonly array $deliveries,
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
            self::str($data, 'text'),
            self::str($data, 'thumbnail_url'),
            self::seq($data, 'deliveries'),
        );
    }
}
