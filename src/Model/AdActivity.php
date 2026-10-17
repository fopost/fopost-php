<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One change recorded on an ad account. */
final class AdActivity extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $eventType,
        public readonly ?string $actorName,
        public readonly ?string $objectName,
        public readonly ?string $objectType,
        public readonly ?string $extraData,
        public readonly ?string $createdAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'event_type'),
            self::str($data, 'actor_name'),
            self::str($data, 'object_name'),
            self::str($data, 'object_type'),
            self::str($data, 'extra_data'),
            self::str($data, 'created_at'),
        );
    }
}
