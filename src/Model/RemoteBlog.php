<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/**
 * A blog on a connected site. $id is the platform's own id, never a FoPost id.
 *
 * A Shopify store reports every blog it has; WordPress has one implicit blog
 * and reports it under the id `default`, so both answer the same shape.
 */
final class RemoteBlog extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $handle,
        public readonly ?string $url,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'title'),
            self::str($data, 'handle'),
            self::str($data, 'url'),
        );
    }
}
