<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** An account a post is targeted at, plus its per account delivery state. */
final class PostAccount extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $platform,
        public readonly ?string $username,
        public readonly ?string $name,
        public readonly ?string $avatar,
        public readonly ?string $publishStatus,
        public readonly ?DateTimeImmutable $postedAt,
        public readonly ?string $platformPostId,
        public readonly ?string $externalUrl,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
        public readonly ?int $attempts,
        public readonly ?int $maxAttempts,
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
            self::str($data, 'username'),
            self::str($data, 'name'),
            self::str($data, 'avatar'),
            self::str($data, 'publish_status'),
            self::date($data, 'posted_at'),
            self::str($data, 'platform_post_id'),
            self::str($data, 'external_url'),
            self::str($data, 'error_code'),
            self::str($data, 'error_message'),
            self::int($data, 'attempts'),
            self::int($data, 'max_attempts'),
        );
    }
}
