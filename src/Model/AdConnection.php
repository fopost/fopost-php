<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** An ad-network grant in a workspace. */
final class AdConnection extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $provider,
        public readonly ?string $authType,
        public readonly string $name,
        public readonly ?string $businessId,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?string $workspaceId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'provider'),
            self::str($data, 'auth_type'),
            self::requiredStr($data, 'name'),
            self::str($data, 'business_id'),
            self::date($data, 'created_at'),
            self::str($data, 'workspace_id'),
        );
    }
}
