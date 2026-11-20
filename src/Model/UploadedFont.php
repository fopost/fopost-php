<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A font file uploaded to a workspace. */
final class UploadedFont extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $family,
        public readonly string $style,
        public readonly ?int $weight,
        public readonly ?string $format,
        /** Membership-checked download URL. */
        public readonly ?string $fileUrl,
        public readonly ?DateTimeImmutable $createdAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'family'),
            self::str($data, 'style') ?? 'normal',
            self::int($data, 'weight'),
            self::str($data, 'format'),
            self::str($data, 'file_url'),
            self::date($data, 'created_at'),
        );
    }
}
