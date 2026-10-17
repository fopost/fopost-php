<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Whether a key is registered. The key itself never comes back. */
final class WhatsappEncryptionKeyStatus extends Model
{
    private function __construct(
        array $raw,
        public readonly bool $hasKey,
        public readonly ?string $signatureStatus,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::bool($data, 'hasKey') ?? false,
            self::str($data, 'signatureStatus'),
        );
    }
}
