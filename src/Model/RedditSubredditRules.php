<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The rules a subreddit publishes, in its own order. */
final class RedditSubredditRules extends Model
{
    /** @param array<int, RedditSubredditRule> $rules */
    private function __construct(
        array $raw,
        public readonly string $subreddit,
        public readonly array $rules,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'subreddit'),
            RedditSubredditRule::listFrom(self::seq($data, 'rules')),
        );
    }
}
