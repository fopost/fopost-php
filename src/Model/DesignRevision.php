<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The design after an update or restore, plus the version that call saved, if any. */
final class DesignRevision extends Model
{
    private function __construct(
        array $raw,
        public readonly Design $design,
        public readonly ?DesignVersion $version,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $version = self::nested($data, 'version');

        return new self(
            $data,
            Design::fromArray(self::map($data, 'data')),
            $version !== null ? DesignVersion::fromArray($version) : null,
        );
    }
}
