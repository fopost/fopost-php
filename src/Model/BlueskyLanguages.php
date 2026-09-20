<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The default post languages for a connection; up to three BCP-47 tags. */
final class BlueskyLanguages extends Model
{
    /**
     * @param array<int|string, mixed> $languages
     */
    private function __construct(
        array $raw,
        public readonly array $languages,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::seq($data, 'languages'),
        );
    }
}
