<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** A lead from Local Services Ads, read live and never stored. */
final class GoogleLocalServicesLead extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly ?string $category,
        public readonly ?string $service,
        public readonly ?string $contactName,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $status,
        public readonly ?string $type,
        public readonly ?string $createdAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'category'),
            self::str($data, 'service'),
            self::str($data, 'contactName'),
            self::str($data, 'phone'),
            self::str($data, 'email'),
            self::str($data, 'status'),
            self::str($data, 'type'),
            self::str($data, 'createdAt'),
        );
    }
}
