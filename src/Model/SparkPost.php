<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A post already live on the network, offered as the source of a Spark ad. */
final class SparkPost extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $identityId,
        public readonly ?string $caption,
        public readonly ?string $thumbnailUrl,
        public readonly ?string $createdAt,
        public readonly ?int $views,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'identity_id'),
            self::str($data, 'caption'),
            self::str($data, 'thumbnail_url'),
            self::str($data, 'created_at'),
            self::int($data, 'views'),
        );
    }
}
