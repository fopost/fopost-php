<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** Where and how to PUT the bytes of a direct upload. */
final class PresignedUpload extends Model
{
    /** @param array<string, string> $headers */
    private function __construct(
        array $raw,
        public readonly string $uploadId,
        public readonly string $uploadUrl,
        public readonly string $method,
        public readonly array $headers,
        public readonly ?DateTimeImmutable $expiresAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        $headers = [];
        foreach (self::map($data, 'headers') as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $headers[$name] = $value;
            }
        }

        return new self(
            $data,
            self::requiredStr($data, 'upload_id'),
            self::requiredStr($data, 'upload_url'),
            self::str($data, 'method') ?? 'PUT',
            $headers,
            self::date($data, 'expires_at'),
        );
    }
}
