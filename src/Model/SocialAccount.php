<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A connected social account. Named to avoid shadowing user accounts. */
final class SocialAccount extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $platform,
        public readonly ?string $workspaceId,
        public readonly ?string $username,
        public readonly ?string $name,
        public readonly ?string $avatar,
        public readonly ?bool $active,
        public readonly ?bool $isPrimary,
        public readonly ?string $healthStatus,
        public readonly ?DateTimeImmutable $lastHealthCheck,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'platform'),
            self::str($data, 'workspace_id'),
            self::str($data, 'username'),
            self::str($data, 'name'),
            self::str($data, 'avatar'),
            self::bool($data, 'active'),
            self::bool($data, 'is_primary'),
            self::str($data, 'health_status'),
            self::date($data, 'last_health_check'),
        );
    }
}
