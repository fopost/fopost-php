<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A post flair, valid only in the subreddit it came from. $editable means the label may be replaced. */
final class RedditFlair extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $text,
        public readonly bool $editable,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'text') ?? '',
            self::bool($data, 'editable') ?? false,
        );
    }
}
