<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The product catalogs one connection reaches. */
final class ProductCatalogsResult extends Model
{
    /** @param array<int, ProductCatalog> $catalogs */
    private function __construct(
        array $raw,
        public readonly array $catalogs,
        public readonly ?string $workspaceId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            ProductCatalog::listFrom(self::seq($data, 'catalogs')),
            self::str($data, 'workspace_id'),
        );
    }
}
