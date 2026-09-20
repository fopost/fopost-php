<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The business profile on a WhatsApp number, plus its quality and limit tier. */
final class WhatsappProfile extends Model
{
    private function __construct(
        array $raw,
        public readonly ?string $about,
        public readonly ?string $address,
        public readonly ?string $description,
        public readonly ?string $email,
        public readonly ?string $vertical,
        public readonly array $websites,
        public readonly ?string $profilePictureUrl,
        public readonly ?string $displayName,
        public readonly ?string $displayNameStatus,
        public readonly ?string $username,
        public readonly ?string $qualityRating,
        public readonly ?string $messagingLimitTier,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'about'),
            self::str($data, 'address'),
            self::str($data, 'description'),
            self::str($data, 'email'),
            self::str($data, 'vertical'),
            is_array($data['websites'] ?? null) ? $data['websites'] : [],
            self::str($data, 'profilePictureUrl'),
            self::str($data, 'displayName'),
            self::str($data, 'displayNameStatus'),
            self::str($data, 'username'),
            self::str($data, 'qualityRating'),
            self::str($data, 'messagingLimitTier'),
        );
    }
}
