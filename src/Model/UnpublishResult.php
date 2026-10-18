<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What unpublishing a post did on each network it was live on. */
final class UnpublishResult extends Model
{
    /** @param array<int, PlatformRemoval> $deliveries */
    private function __construct(
        array $raw,
        public readonly string $postStatus,
        public readonly array $deliveries,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'post_status'),
            PlatformRemoval::listFrom(self::seq($data, 'deliveries')),
        );
    }
}
