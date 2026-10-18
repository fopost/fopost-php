<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The settings after a patch, plus the website secret when this patch issued one. */
final class BlogSettingsUpdate extends Model
{
    private function __construct(
        array $raw,
        public readonly BlogSettings $settings,
        /** Set only when websiteUrl was set for the first time; never readable again. */
        public readonly ?string $websiteSecret,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            BlogSettings::fromArray($data),
            self::str($data, 'websiteSecret'),
        );
    }
}
