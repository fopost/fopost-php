<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** How many iOS 14 campaigns an ad account may run at once, per app. */
final class IosCampaignLimits extends Model
{
    private function __construct(
        array $raw,
        public readonly ?int $limit,
        public readonly ?int $used,
        public readonly ?string $appId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::int($data, 'limit'),
            self::int($data, 'used'),
            self::str($data, 'app_id'),
        );
    }
}
