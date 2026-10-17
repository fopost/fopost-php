<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** The switches TikTok enforces at publish time, set on the account itself. */
final class TikTokCreatorInfo extends Model
{
    /**
     * @param array<int|string, mixed> $privacyLevelOptions
     */
    private function __construct(
        array $raw,
        public readonly ?string $username,
        public readonly ?string $nickname,
        public readonly ?string $avatarUrl,
        public readonly array $privacyLevelOptions,
        public readonly bool $commentDisabled,
        public readonly bool $duetDisabled,
        public readonly bool $stitchDisabled,
        public readonly ?int $maxVideoPostDurationSec,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::str($data, 'username'),
            self::str($data, 'nickname'),
            self::str($data, 'avatar_url'),
            self::seq($data, 'privacy_level_options'),
            self::bool($data, 'comment_disabled') ?? false,
            self::bool($data, 'duet_disabled') ?? false,
            self::bool($data, 'stitch_disabled') ?? false,
            self::int($data, 'max_video_post_duration_sec'),
        );
    }
}
