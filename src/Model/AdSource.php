<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A connection with the ad accounts and Pages its grant reaches. */
final class AdSource extends Model
{
    /**
     * @param array<int, array<string, mixed>> $adAccounts
     * @param array<int, array<string, mixed>> $pages
     */
    private function __construct(
        array $raw,
        public readonly string $connectionId,
        public readonly string $name,
        public readonly ?string $workspaceId,
        public readonly array $adAccounts,
        public readonly array $pages,
        public readonly ?string $error,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'connection_id'),
            self::requiredStr($data, 'name'),
            self::str($data, 'workspace_id'),
            self::seq($data, 'ad_accounts'),
            self::seq($data, 'pages'),
            self::str($data, 'error'),
        );
    }
}
