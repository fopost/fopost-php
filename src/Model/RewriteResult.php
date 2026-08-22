<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One rewrite call: a variant per requested platform. */
final class RewriteResult extends Model
{
    /** @param array<int, RewriteVariant> $results */
    private function __construct(
        array $raw,
        public readonly array $results,
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
            RewriteVariant::listFrom(self::seq($data, 'results')),
            $credits !== null ? AiCredits::fromArray($credits) : null,
        );
    }
}
