<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** An in-chat form. The platform validates it and owns its status. */
final class WhatsappFlow extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly string $status,
        public readonly array $categories,
        public readonly array $validationErrors,
        public readonly ?string $endpointUri,
        public readonly ?string $jsonVersion,
        public readonly ?string $previewUrl,
        public readonly ?\DateTimeImmutable $previewExpiresAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'id') ?? '',
            self::str($data, 'name') ?? '',
            self::str($data, 'status') ?? '',
            is_array($data['categories'] ?? null) ? $data['categories'] : [],
            is_array($data['validationErrors'] ?? null) ? $data['validationErrors'] : [],
            self::str($data, 'endpointUri'),
            self::str($data, 'jsonVersion'),
            self::str($data, 'previewUrl'),
            self::date($data, 'previewExpiresAt'),
        );
    }
}
