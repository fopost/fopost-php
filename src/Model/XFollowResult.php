<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The outcome of following or unfollowing an X user from a connected account. */
final class XFollowResult extends Model
{
    private function __construct(
        array $raw,
        public readonly string $accountId,
        public readonly string $userId,
        public readonly bool $following,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'account_id'),
            self::requiredStr($data, 'user_id'),
            self::bool($data, 'following') ?? false,
        );
    }
}
