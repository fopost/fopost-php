<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The account's assets, with the links that put each one under an ad. */
final class GoogleAssetsResult extends Model
{
    /**
     * @param array<int, GoogleAsset> $assets
     * @param array<int, GoogleAssetLink> $links
     */
    private function __construct(
        array $raw,
        public readonly array $assets,
        public readonly array $links,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            GoogleAsset::listFrom(self::seq($data, 'assets')),
            GoogleAssetLink::listFrom(self::seq($data, 'links')),
        );
    }
}
