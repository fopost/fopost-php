<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Post flairs one subreddit offers. A flair id is valid only there. */
final class RedditFlairs extends Model
{
    /** @param array<int, RedditFlair> $flairs */
    private function __construct(
        array $raw,
        public readonly string $subreddit,
        public readonly array $flairs,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'subreddit'),
            RedditFlair::listFrom(self::seq($data, 'flairs')),
        );
    }
}
