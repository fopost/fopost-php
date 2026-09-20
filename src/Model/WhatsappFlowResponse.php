<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What one person submitted through a flow. */
final class WhatsappFlowResponse extends Model
{
    private function __construct(
        array $raw,
        public readonly string $messageId,
        public readonly ?string $waId,
        public readonly ?string $flowToken,
        public readonly array $answers,
        public readonly ?\DateTimeImmutable $respondedAt,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'messageId') ?? '',
            self::str($data, 'waId'),
            self::str($data, 'flowToken'),
            is_array($data['answers'] ?? null) ? $data['answers'] : [],
            self::date($data, 'respondedAt'),
        );
    }
}
