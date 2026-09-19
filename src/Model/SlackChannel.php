<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A Slack channel the app can post to; $isCurrent marks the one this account posts to. */
final class SlackChannel extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly bool $isPrivate,
        public readonly bool $isMember,
        public readonly bool $isCurrent,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'name'),
            self::bool($data, 'is_private') ?? false,
            self::bool($data, 'is_member') ?? false,
            self::bool($data, 'is_current') ?? false,
        );
    }
}
