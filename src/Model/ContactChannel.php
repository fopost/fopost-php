<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One handle on one network. The handle is lower-cased with no leading @. */
final class ContactChannel extends Model
{
    private function __construct(
        array $raw,
        public readonly string $platform,
        public readonly string $handle,
        /** The platform's own id for this person, when the network gave us one. */
        public readonly ?string $externalId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'platform'),
            self::requiredStr($data, 'handle'),
            self::str($data, 'external_id'),
        );
    }

    /** The wire shape a write sends. */
    public static function make(string $platform, string $handle, ?string $externalId = null): self
    {
        return self::fromArray(array_filter(
            ['platform' => $platform, 'handle' => $handle, 'externalId' => $externalId],
            static fn (mixed $v): bool => $v !== null,
        ));
    }
}
