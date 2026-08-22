<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One post to account delivery attempt. */
final class Delivery extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $accountId,
        public readonly ?string $status,
        public readonly ?string $platform,
        public readonly ?string $username,
        public readonly ?string $accountName,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
        public readonly ?int $attempts,
        public readonly ?int $maxAttempts,
        public readonly ?DateTimeImmutable $scheduledPublishAt,
        public readonly ?string $delayReason,
        public readonly ?string $delayMessage,
        public readonly ?DateTimeImmutable $postedAt,
        public readonly ?DateTimeImmutable $lastAttemptAt,
        public readonly ?string $platformPostId,
        public readonly ?string $externalUrl,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'account_id'),
            self::str($data, 'status'),
            self::str($data, 'platform'),
            self::str($data, 'username'),
            self::str($data, 'account_name'),
            self::str($data, 'error_code'),
            self::str($data, 'error_message'),
            self::int($data, 'attempts'),
            self::int($data, 'max_attempts'),
            self::date($data, 'scheduled_publish_at'),
            self::str($data, 'delay_reason'),
            self::str($data, 'delay_message'),
            self::date($data, 'posted_at'),
            self::date($data, 'last_attempt_at'),
            self::str($data, 'platform_post_id'),
            self::str($data, 'external_url'),
        );
    }
}
