<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Whether the cart and catalog show on the number, and which catalog is linked. */
final class WhatsappCommerceSettings extends Model
{
    private function __construct(
        array $raw,
        public readonly ?bool $cartEnabled,
        public readonly ?bool $catalogVisible,
        public readonly ?string $catalogId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::bool($data, 'cartEnabled'),
            self::bool($data, 'catalogVisible'),
            self::str($data, 'catalogId'),
        );
    }
}
