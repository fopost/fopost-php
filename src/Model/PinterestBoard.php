<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A Pinterest board; $id travels as the board_id platform setting to pin to it. */
final class PinterestBoard extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $privacy,
        public readonly ?string $description,
        public readonly ?string $image,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::requiredStr($data, 'name'),
            self::str($data, 'privacy'),
            self::str($data, 'description'),
            self::str($data, 'image'),
        );
    }
}
