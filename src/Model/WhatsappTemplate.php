<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A message template on a WhatsApp Business Account; the platform owns its status. */
final class WhatsappTemplate extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly string $language,
        public readonly string $category,
        public readonly string $status,
        public readonly ?string $rejectedReason,
        public readonly array $components,
        public readonly ?string $qualityScore,
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
            self::str($data, 'language') ?? '',
            self::str($data, 'category') ?? '',
            self::str($data, 'status') ?? '',
            self::str($data, 'rejectedReason'),
            is_array($data['components'] ?? null) ? $data['components'] : [],
            self::str($data, 'qualityScore'),
        );
    }
}
