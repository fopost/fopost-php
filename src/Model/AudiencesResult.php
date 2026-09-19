<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The audiences and pixels on one ad account. */
final class AudiencesResult extends Model
{
    /**
     * @param array<int, Audience> $audiences
     * @param array<int, array<string, mixed>> $pixels
     */
    private function __construct(
        array $raw,
        public readonly array $audiences,
        public readonly array $pixels,
        public readonly ?string $workspaceId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            Audience::listFrom(self::seq($data, 'audiences')),
            self::seq($data, 'pixels'),
            self::str($data, 'workspace_id'),
        );
    }
}
