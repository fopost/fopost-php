<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What the website answered to a signed test delivery. */
final class BlogWebsiteTest extends Model
{
    private function __construct(
        array $raw,
        public readonly bool $ok,
        /** The HTTP status the site returned, null when it could not be reached. */
        public readonly ?int $status,
        public readonly ?string $error,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::bool($data, 'ok') ?? false,
            self::int($data, 'status'),
            self::str($data, 'error'),
        );
    }
}
