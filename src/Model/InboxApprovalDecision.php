<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The outcome of approving or rejecting a drafted reply. */
final class InboxApprovalDecision extends Model
{
    private function __construct(
        array $raw,
        public readonly int $id,
        public readonly ?string $outcome,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::int($data, 'id') ?? 0, self::str($data, 'outcome'));
    }
}
