<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A hosted connect link handed to one of your users. */
final class ConnectSession extends Model
{
    /**
     * @param array<int, string> $platforms
     * @param array{name: ?string, logo_url: ?string}|null $branding
     */
    private function __construct(
        array $raw,
        public readonly string $id,
        /** pending, used or expired. */
        public readonly string $status,
        public readonly string $externalUserId,
        public readonly array $platforms,
        public readonly ?string $redirectUrl,
        public readonly ?array $branding,
        /** The connected account, once the session is used. */
        public readonly ?string $accountId,
        /** The hosted link to hand to your user. Present on creation only. */
        public readonly ?string $url,
        public readonly ?DateTimeImmutable $expiresAt,
        public readonly ?DateTimeImmutable $usedAt,
        public readonly ?DateTimeImmutable $createdAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'status'),
            self::requiredStr($data, 'external_user_id'),
            array_values(array_map(strval(...), self::seq($data, 'platforms'))),
            self::str($data, 'redirect_url'),
            self::nested($data, 'branding'),
            self::str($data, 'account_id'),
            self::str($data, 'url'),
            self::date($data, 'expires_at'),
            self::date($data, 'used_at'),
            self::date($data, 'created_at'),
        );
    }
}
