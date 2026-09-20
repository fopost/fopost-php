<?php

declare(strict_types=1);

namespace Fopost\Sdk;

/**
 * Every platform the API can publish to, and every status a post can hold.
 *
 * Model fields stay plain strings, so a platform added server side still
 * parses on an older SDK.
 */
final class Platforms
{
    /** @var array<int, string> */
    public const ALL = [
        'twitter',
        'linkedin',
        'facebook',
        'instagram',
        'instagram-business',
        'telegram',
        'twitch',
        'discord',
        'slack',
        'reddit',
        'pinterest',
        'tumblr',
        'dribbble',
        'mewe',
        'tiktok',
        'youtube',
        'bluesky',
        'threads',
        'mastodon',
        'lemmy',
        'devto',
        'hashnode',
        'medium',
        'substack',
        'google-business',
        'kick',
        'listmonk',
        'wordpress',
        'nostr',
        'whop',
        'skool',
        'whatsapp',
    ];

    /** @var array<int, string> */
    public const POST_STATUSES = [
        'draft',
        'pending_approval',
        'scheduled',
        'publishing',
        'published',
        'failed',
        'cancelled',
    ];

    private function __construct()
    {
    }
}
