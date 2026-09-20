<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What the platform took and what it refused. */
final class WhatsappBlockResult extends Model
{
    private function __construct(
        array $raw,
        public readonly array $blocked,
        public readonly array $unblocked,
        public readonly array $failed,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            is_array($data['blocked'] ?? null) ? $data['blocked'] : [],
            is_array($data['unblocked'] ?? null) ? $data['unblocked'] : [],
            is_array($data['failed'] ?? null) ? $data['failed'] : [],
        );
    }
}
