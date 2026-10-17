<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** An event on the server's calendar; $channelId is a voice or stage channel, else $location says where. */
final class DiscordScheduledEvent extends Model
{
    private function __construct(
        array $raw,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $channelId,
        public readonly ?string $location,
        public readonly string $startTime,
        public readonly ?string $endTime,
        /** One of scheduled, active, completed, canceled. */
        public readonly string $status,
        public readonly ?int $userCount,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'id'),
            self::str($data, 'name') ?? '',
            self::str($data, 'description'),
            self::str($data, 'channel_id'),
            self::str($data, 'location'),
            self::str($data, 'start_time') ?? '',
            self::str($data, 'end_time'),
            self::str($data, 'status') ?? 'scheduled',
            self::int($data, 'user_count'),
        );
    }
}
