<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A Page subscribed to new leads, so they land in the leads feed. */
final class LeadPage extends Model
{
    private function __construct(
        array $raw,
        public readonly string $connectionId,
        public readonly string $pageId,
        public readonly ?string $pageName,
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
            self::requiredStr($data, 'connection_id'),
            self::requiredStr($data, 'page_id'),
            self::str($data, 'page_name'),
            self::date($data, 'created_at'),
            self::str($data, 'workspace_id'),
        );
    }
}
