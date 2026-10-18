<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** One API request the key or a session made. */
final class RequestLog extends Model
{
    private function __construct(
        array $raw,
        public readonly int $id,
        /** Null for account-level requests. */
        public readonly ?string $workspaceId,
        public readonly ?DateTimeImmutable $time,
        /** jwt or api_key. */
        public readonly ?string $authMethod,
        public readonly string $method,
        /** The route pattern, e.g. /v1/posts/{id}. */
        public readonly ?string $route,
        public readonly ?string $path,
        public readonly int $statusCode,
        public readonly ?int $durationMs,
        public readonly ?string $platform,
        /** Error text for a failed request. */
        public readonly ?string $message,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'id') ?? 0,
            self::str($data, 'workspace_id'),
            self::date($data, 'time'),
            self::str($data, 'auth_method'),
            self::requiredStr($data, 'method'),
            self::str($data, 'route'),
            self::str($data, 'path'),
            self::int($data, 'status_code') ?? 0,
            self::int($data, 'duration_ms'),
            self::str($data, 'platform'),
            self::str($data, 'message'),
        );
    }
}
