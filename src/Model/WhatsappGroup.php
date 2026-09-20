<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A group on a WhatsApp number. Participation is invite-only. */
final class WhatsappGroup extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $subject,
        public readonly ?string $description,
        public readonly ?int $participantCount,
        public readonly ?string $inviteLink,
        public readonly ?\DateTimeImmutable $createdAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'id') ?? '',
            self::str($data, 'subject') ?? '',
            self::str($data, 'description'),
            self::int($data, 'participantCount'),
            self::str($data, 'inviteLink'),
            self::date($data, 'createdAt'),
        );
    }
}
