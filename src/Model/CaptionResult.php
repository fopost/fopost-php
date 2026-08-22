<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What a caption generation call returns. */
final class CaptionResult extends Model
{
    private function __construct(
        array $raw,
        public readonly string $caption,
        public readonly ?AiCredits $credits,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $credits = self::nested($data, 'credits');

        return new self(
            $data,
            self::requiredStr($data, 'caption'),
            $credits !== null ? AiCredits::fromArray($credits) : null,
        );
    }
}
