<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Totals across the selected accounts for the period. */
final class AnalyticsOverview extends Model
{
    /**
     * @param array<string, float|null> $deltas Percentage change vs the previous period, by metric.
     * @param array{posts: int, follower_change: int, engagement: int} $todayStats
     * @param array<int, array{platform: string, accounts: int, followers: int}> $platforms
     * @param array<int, AnalyticsAccountTotal> $accounts
     */
    private function __construct(
        array $raw,
        public readonly int $totalAccounts,
        public readonly int $totalFollowers,
        public readonly int $totalPosts,
        public readonly int $totalEngagement,
        public readonly int $totalImpressions,
        public readonly int $totalReach,
        public readonly int $totalLikes,
        public readonly int $totalComments,
        public readonly int $totalShares,
        public readonly int $totalReposts,
        public readonly int $totalSaves,
        public readonly int $totalClicks,
        public readonly int $totalVideoViews,
        public readonly int $totalProfileViews,
        /** Engagement over reach (or impressions); null without data. */
        public readonly ?float $engagementRate,
        public readonly array $deltas,
        public readonly array $todayStats,
        public readonly array $platforms,
        public readonly array $accounts,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $deltas = [];
        foreach (self::map($data, 'deltas') as $metric => $delta) {
            $deltas[(string) $metric] = is_int($delta) || is_float($delta) ? (float) $delta : null;
        }
        $today = self::map($data, 'today_stats');

        return new self(
            $data,
            self::int($data, 'total_accounts') ?? 0,
            self::int($data, 'total_followers') ?? 0,
            self::int($data, 'total_posts') ?? 0,
            self::int($data, 'total_engagement') ?? 0,
            self::int($data, 'total_impressions') ?? 0,
            self::int($data, 'total_reach') ?? 0,
            self::int($data, 'total_likes') ?? 0,
            self::int($data, 'total_comments') ?? 0,
            self::int($data, 'total_shares') ?? 0,
            self::int($data, 'total_reposts') ?? 0,
            self::int($data, 'total_saves') ?? 0,
            self::int($data, 'total_clicks') ?? 0,
            self::int($data, 'total_video_views') ?? 0,
            self::int($data, 'total_profile_views') ?? 0,
            self::num($data, 'engagement_rate'),
            $deltas,
            [
                'posts' => self::int($today, 'posts') ?? 0,
                'follower_change' => self::int($today, 'follower_change') ?? 0,
                'engagement' => self::int($today, 'engagement') ?? 0,
            ],
            array_values(array_filter(self::seq($data, 'platforms'), is_array(...))),
            AnalyticsAccountTotal::listFrom(self::seq($data, 'accounts')),
        );
    }
}
