<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The next connected-accounts tier and the file size it unlocks. */
final class UploadLimitTier extends Model
{
    private function __construct(
        array $raw,
        public readonly ?int $upToAccounts,
        public readonly int $maxFileBytes,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'up_to_accounts'),
            self::int($data, 'max_file_bytes') ?? 0,
        );
    }
}
