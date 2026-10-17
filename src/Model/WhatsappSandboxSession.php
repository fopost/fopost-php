<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A sandbox invitation. The tester's number is never stored in full. */
final class WhatsappSandboxSession extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $status,
        public readonly string $phoneNumberLast4,
        public readonly ?\DateTimeImmutable $invitedAt,
        public readonly ?\DateTimeImmutable $activatedAt,
        public readonly ?\DateTimeImmutable $expiresAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'id') ?? '',
            self::str($data, 'status') ?? '',
            self::str($data, 'phoneNumberLast4') ?? '',
            self::date($data, 'invitedAt'),
            self::date($data, 'activatedAt'),
            self::date($data, 'expiresAt'),
        );
    }
}
