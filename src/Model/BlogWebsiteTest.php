<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What the website answered a test delivery it accepted. */
final class BlogWebsiteTest extends Model
{
    private function __construct(
        array $raw,
        public readonly ?int $httpStatus,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::int($data, 'httpStatus'));
    }
}
