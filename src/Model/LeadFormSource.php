<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A connection's Page and the lead forms on it. */
final class LeadFormSource extends Model
{
    /** @param array<int, LeadForm> $forms */
    private function __construct(
        array $raw,
        public readonly string $connectionId,
        public readonly ?string $connectionName,
        public readonly ?string $pageId,
        public readonly ?string $pageName,
        public readonly array $forms,
        public readonly ?string $error,
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
            self::str($data, 'connection_name'),
            self::str($data, 'page_id'),
            self::str($data, 'page_name'),
            LeadForm::listFrom(self::seq($data, 'forms')),
            self::str($data, 'error'),
            self::str($data, 'workspace_id'),
        );
    }
}
