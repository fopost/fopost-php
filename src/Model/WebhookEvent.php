<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

use DateTimeImmutable;

/** A verified webhook envelope: the event name, its data and when it was raised. */
final class WebhookEvent extends Model
{
    /** @param array<string, mixed> $data */
    private function __construct(
        array $raw,
        public readonly string $event,
        public readonly array $data,
        public readonly ?DateTimeImmutable $timestamp,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'event'),
            self::map($data, 'data'),
            self::date($data, 'timestamp'),
        );
    }

    /** The typed payload of an article.published or article.test delivery. */
    public function article(): BlogArticleWebhookPayload
    {
        return BlogArticleWebhookPayload::fromArray($this->data);
    }
}
