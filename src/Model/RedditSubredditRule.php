<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One rule a subreddit publishes. $appliesTo is link, comment or all. */
final class RedditSubredditRule extends Model
{
    private function __construct(
        array $raw,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $appliesTo,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'name'),
            self::str($data, 'description'),
            self::str($data, 'applies_to') ?? 'all',
        );
    }
}
