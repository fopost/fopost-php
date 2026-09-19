<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The result of moving an account to another workspace. */
final class AccountMove extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $workspaceId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::requiredStr($data, 'id'), self::requiredStr($data, 'workspace_id'));
    }
}
