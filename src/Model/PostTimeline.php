<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Every reading held for one post, one timeline per delivery. */
final class PostTimeline extends Model
{
    /** @param array<int, TimelineDelivery> $deliveries */
    private function __construct(
        array $raw,
        /** Null when the post was made natively on the network. */
        public readonly ?string $postId,
        public readonly array $deliveries,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'post_id'),
            TimelineDelivery::listFrom(self::seq($data, 'deliveries')),
        );
    }
}
