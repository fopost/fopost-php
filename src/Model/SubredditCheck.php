<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** $ok is true when the subreddit exists and takes a post from this account. */
final class SubredditCheck extends Model
{
    private function __construct(
        array $raw,
        public readonly string $subreddit,
        public readonly bool $exists,
        public readonly bool $canPost,
        public readonly bool $over18,
        public readonly bool $flairEnabled,
        public readonly bool $ok,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'subreddit'),
            self::bool($data, 'exists') ?? false,
            self::bool($data, 'can_post') ?? false,
            self::bool($data, 'over_18') ?? false,
            self::bool($data, 'flair_enabled') ?? false,
            self::bool($data, 'ok') ?? false,
        );
    }
}
