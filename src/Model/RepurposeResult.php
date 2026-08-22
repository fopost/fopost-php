<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** An article turned into a post for each requested platform. */
final class RepurposeResult extends Model
{
    /** @param array<string, string> $posts */
    private function __construct(
        array $raw,
        public readonly string $url,
        public readonly ?string $title,
        public readonly array $posts,
        public readonly ?AiCredits $credits,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $credits = self::nested($data, 'credits');

        $posts = [];
        foreach (self::map($data, 'posts') as $platform => $content) {
            if (is_string($content)) {
                $posts[(string) $platform] = $content;
            }
        }

        return new self(
            $data,
            self::requiredStr($data, 'url'),
            self::str($data, 'title'),
            $posts,
            $credits !== null ? AiCredits::fromArray($credits) : null,
        );
    }
}
