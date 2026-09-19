<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A connected account, flagged with whether comments and DMs can be read for it. */
final class InboxAccount extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $workspaceId,
        public readonly string $platform,
        public readonly ?string $username,
        public readonly ?string $name,
        public readonly ?string $avatar,
        public readonly ?bool $inboxSupported,
        public readonly ?string $pendingReason,
        public readonly ?bool $dmSupported,
        public readonly ?string $dmPendingReason,
        public readonly ?bool $canStartConversation,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'workspace_id'),
            self::requiredStr($data, 'platform'),
            self::str($data, 'username'),
            self::str($data, 'name'),
            self::str($data, 'avatar'),
            self::bool($data, 'inbox_supported'),
            self::str($data, 'pending_reason'),
            self::bool($data, 'dm_supported'),
            self::str($data, 'dm_pending_reason'),
            self::bool($data, 'can_start_conversation'),
        );
    }
}
