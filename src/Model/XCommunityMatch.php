<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A community found on X by name, not yet linked. */
final class XCommunityMatch extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?int $memberCount,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'name'),
            self::str($data, 'description'),
            self::int($data, 'member_count'),
        );
    }
}
