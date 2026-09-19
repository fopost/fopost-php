<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Where posts go when a post names no subreddit; null means the account's own profile page. */
final class RedditDefaultSubreddit extends Model
{
    private function __construct(
        array $raw,
        public readonly ?string $subreddit,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'subreddit'),
        );
    }
}
