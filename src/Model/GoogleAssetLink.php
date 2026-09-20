<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Where an asset is attached; an asset with no links serves nowhere. */
final class GoogleAssetLink extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $assetId,
        public readonly string $level,
        public readonly ?string $ownerId,
        public readonly string $fieldType,
        public readonly string $status,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'assetId'),
            self::requiredStr($data, 'level'),
            self::str($data, 'ownerId'),
            self::requiredStr($data, 'fieldType'),
            self::requiredStr($data, 'status'),
        );
    }
}
