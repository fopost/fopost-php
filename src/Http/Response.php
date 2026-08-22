<?php

declare(strict_types=1);

namespace Fopost\Sdk\Http;

/** A raw HTTP response, before any JSON decoding. */
final class Response
{
    /** @param array<string, string> $headers Lowercased header names. */
    public function __construct(
        public readonly int $status,
        public readonly array $headers = [],
        public readonly string $body = '',
    ) {
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function isSuccess(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }
}
