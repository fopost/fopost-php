<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The outcome of a Messenger thread hand-over. */
final class InboxHandover extends Model
{
    private function __construct(
        array $raw,
        /** The app control went to, or null when it was taken back. */
        public readonly ?string $appId,
        /** `passed` or `taken`. */
        public readonly string $control,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self($data, self::str($data, 'app_id'), self::requiredStr($data, 'control'));
    }
}
