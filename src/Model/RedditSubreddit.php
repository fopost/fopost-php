<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A subreddit the account is in, or its own profile page. $canPost is false where it may read but not submit. */
final class RedditSubreddit extends Model
{
    private function __construct(
        array $raw,
        public readonly string $name,
        public readonly ?string $title,
        public readonly ?int $subscribers,
        public readonly bool $over18,
        public readonly bool $canPost,
        public readonly bool $flairEnabled,
        public readonly ?string $iconUrl,
        public readonly bool $isDefault,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'name'),
            self::str($data, 'title'),
            self::int($data, 'subscribers'),
            self::bool($data, 'over18') ?? false,
            self::bool($data, 'can_post') ?? true,
            self::bool($data, 'flair_enabled') ?? false,
            self::str($data, 'icon_url'),
            self::bool($data, 'is_default') ?? false,
        );
    }
}
