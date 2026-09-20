<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** What the network delivers to the FoPost webhook for one account. */
final class WebhookSubscription extends Model
{
    /**
     * @param array<int, string> $fields
     * @param array<int, string> $missingFields
     */
    private function __construct(
        array $raw,
        public readonly bool $subscribed,
        public readonly array $fields,
        public readonly array $missingFields,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::bool($data, 'subscribed') ?? false,
            array_values(array_map(strval(...), self::seq($data, 'fields'))),
            array_values(array_map(strval(...), self::seq($data, 'missing_fields'))),
        );
    }
}
