<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What the account may upload: the per-file ceiling, how it was earned, and the routes. */
final class UploadLimits extends Model
{
    /** @param array<int, string> $allowedTypes */
    private function __construct(
        array $raw,
        public readonly int $maxFileBytes,
        public readonly string $source,
        public readonly int $connectedAccounts,
        public readonly bool $largeUploadsEnabled,
        public readonly ?UploadLimitTier $nextTier,
        public readonly int $multipartMaxFileBytes,
        public readonly int $maxFilesPerUpload,
        public readonly bool $directUploadAvailable,
        public readonly array $allowedTypes,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $tier = self::nested($data, 'next_tier');

        return new self(
            $data,
            self::int($data, 'max_file_bytes') ?? 0,
            self::requiredStr($data, 'source'),
            self::int($data, 'connected_accounts') ?? 0,
            self::bool($data, 'large_uploads_enabled') ?? false,
            $tier !== null ? UploadLimitTier::fromArray($tier) : null,
            self::int($data, 'multipart_max_file_bytes') ?? 0,
            self::int($data, 'max_files_per_upload') ?? 0,
            self::bool($data, 'direct_upload_available') ?? false,
            array_values(array_filter(self::seq($data, 'allowed_types'), 'is_string')),
        );
    }
}
