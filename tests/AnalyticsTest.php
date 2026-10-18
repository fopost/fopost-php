<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class AnalyticsTest extends TestCase
{
    public function testDecayReadsTheBandsAndHalfLife(): void
    {
        $this->transport->push(200, ['data' => [
            'days' => 30,
            'postsMeasured' => 2,
            'halfLifeBucket' => '1h_3h',
            'bands' => [
                [
                    'bucket' => 'under_1h',
                    'label' => 'First hour',
                    'posts' => 2,
                    'avgEngagements' => 25,
                    'avgImpressions' => 300,
                    'shareOfFinal' => 0.3,
                ],
                [
                    'bucket' => '6h_12h',
                    'label' => '6-12 hours',
                    'posts' => 0,
                    'avgEngagements' => 0,
                    'avgImpressions' => 0,
                    'shareOfFinal' => null,
                ],
            ],
        ]]);

        $decay = $this->client()->analytics()->decay(30, accountId: 'acc_1');

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/analytics/decay?days=30&accountId=acc_1',
            $this->transport->last()['url'],
        );
        $this->assertSame('1h_3h', $decay->halfLifeBucket);
        $this->assertSame(2, $decay->postsMeasured);
        $this->assertSame(0.3, $decay->bands[0]->shareOfFinal);
        // A band nothing was measured in reports no share rather than zero
        $this->assertNull($decay->bands[1]->shareOfFinal);
    }

    public function testFrequencyReadsTheWeeksAndBestCadence(): void
    {
        $this->transport->push(200, ['data' => [
            'days' => 90,
            'weeks' => [
                ['weekStart' => '2026-03-02', 'posts' => 2, 'engagements' => 240, 'avgEngagementsPerPost' => 120],
            ],
            'bands' => [
                [
                    'band' => 'under_3',
                    'label' => '1-2 a week',
                    'weeks' => 1,
                    'posts' => 2,
                    'avgPostsPerWeek' => 2,
                    'avgEngagementsPerPost' => 120,
                    'engagementRate' => 0.12,
                ],
            ],
            'best' => ['band' => 'under_3', 'label' => '1-2 a week', 'avgEngagementsPerPost' => 120],
        ]]);

        $frequency = $this->client()->analytics()->frequency(90);

        $this->assertSame('2026-03-02', $frequency->weeks[0]->weekStart);
        $this->assertSame(120.0, $frequency->weeks[0]->avgEngagementsPerPost);
        $this->assertSame(0.12, $frequency->bands[0]->engagementRate);
        $this->assertNotNull($frequency->best);
        $this->assertSame('1-2 a week', $frequency->best->label);
    }

    public function testTimelineEscapesAPermalinkIntoThePath(): void
    {
        $this->transport->push(200, ['data' => [
            'postId' => null,
            'deliveries' => [[
                'accountId' => 'acc_1',
                'platform' => 'twitter',
                'username' => 'acme',
                'externalPostId' => '1',
                'postedAt' => '2026-03-02T00:00:00.000Z',
                'points' => [[
                    'at' => '2026-03-02T00:30:00.000Z',
                    'ageMinutes' => 30,
                    'engagements' => 40,
                    'impressions' => 400,
                    'reach' => null,
                    'likes' => 30,
                    'comments' => null,
                    'shares' => null,
                    'videoViews' => null,
                    'delta' => [
                        'impressions' => 400,
                        'reach' => 0,
                        'engagements' => 40,
                        'likes' => 30,
                        'comments' => 0,
                        'shares' => 0,
                    ],
                ]],
            ]],
        ]]);

        $timeline = $this->client()->analytics()->timeline('https://x.com/acme/status/1');

        $this->assertSame(
            'https://api.fopost.com/v1/analytics/posts/https%3A%2F%2Fx.com%2Facme%2Fstatus%2F1/timeline',
            $this->transport->last()['url'],
        );
        $this->assertNull($timeline->postId);
        $this->assertSame(30, $timeline->deliveries[0]->points[0]->ageMinutes);
        $this->assertSame(40, $timeline->deliveries[0]->points[0]->delta->engagements);
    }

    public function testChangesCarriesTheCursor(): void
    {
        $this->transport->push(200, ['data' => [
            'since' => '2026-03-02T00:00:00.000Z',
            'cursor' => '2026-03-02T06:00:00.000Z',
            'hasMore' => true,
            'changes' => [[
                'accountId' => 'acc_1',
                'platform' => 'twitter',
                'externalPostId' => '1',
                'postId' => 'post_1',
                'postedAt' => '2026-03-02T00:00:00.000Z',
                'fetchedAt' => '2026-03-02T06:00:00.000Z',
                'impressions' => 900,
                'reach' => null,
                'engagements' => 90,
                'likes' => 70,
                'comments' => 10,
                'shares' => 10,
            ]],
        ]]);

        $page = $this->client()->analytics()->changes('2026-03-02T00:00:00Z', 100);

        $this->assertStringContainsString('since=2026-03-02T00%3A00%3A00Z', $this->transport->last()['url']);
        $this->assertStringContainsString('limit=100', $this->transport->last()['url']);
        $this->assertTrue($page->hasMore);
        $this->assertSame('post_1', $page->changes[0]->postId);
    }

    public function testCollectPostReportsEachDelivery(): void
    {
        $this->transport->push(200, ['data' => [
            'collected' => 1,
            'deliveries' => [[
                'accountId' => 'acc_1',
                'platform' => 'twitter',
                'externalPostId' => '1',
                'collected' => true,
                'fetchedAt' => '2026-03-02T00:30:00.000Z',
                'message' => null,
            ]],
        ]]);

        $result = $this->client()->analytics()->collectPost('post_1');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/posts/post_1/analytics/collect',
            $this->transport->last()['url'],
        );
        $this->assertSame(1, $result->collected);
        $this->assertTrue($result->deliveries[0]->collected);
    }

    public function testNativePostsReturnsAPage(): void
    {
        $this->transport->push(200, [
            'data' => [[
                'externalPostId' => '1',
                'text' => 'Posted by hand',
                'permalink' => 'https://x.com/acme/status/1',
                'thumbnailUrl' => null,
                'mediaType' => null,
                'postedAt' => '2026-03-02T00:00:00.000Z',
                'fetchedAt' => '2026-03-02T06:00:00.000Z',
                'metrics' => [
                    'impressions' => 900,
                    'reach' => null,
                    'engagements' => 90,
                    'likes' => 70,
                    'comments' => 10,
                    'shares' => 10,
                    'videoViews' => null,
                ],
            ]],
            'meta' => ['page' => 1, 'perPage' => 20, 'total' => 1],
        ]);

        $page = $this->client()->analytics()->nativePosts('acc_1');

        $this->assertSame(
            'https://api.fopost.com/v1/accounts/acc_1/native-posts?page=1&per_page=20',
            $this->transport->last()['url'],
        );
        $this->assertCount(1, $page->items);
        $this->assertSame('https://x.com/acme/status/1', $page->items[0]->permalink);
        $this->assertSame(90, $page->items[0]->metrics->engagements);
    }

    public function testOverviewTimeSeriesAndTopPostsReadTheDashboardNumbers(): void
    {
        $this->transport->push(200, [
            'total_accounts' => 2,
            'total_followers' => 1200,
            'total_posts' => 8,
            'total_engagement' => 300,
            'engagement_rate' => 0.05,
            'deltas' => ['followers' => 2.5, 'posts' => null],
            'today_stats' => ['posts' => 1, 'follower_change' => 3, 'engagement' => 20],
            'platforms' => [['platform' => 'bluesky', 'accounts' => 1, 'followers' => 200]],
            'accounts' => [[
                'account_id' => 'a_1',
                'platform' => 'bluesky',
                'username' => 'yourbrand',
                'name' => 'Your Brand',
                'followers' => 200,
                'history' => [['date' => '2026-03-01', 'followers' => 198]],
            ]],
        ]);
        $this->transport->push(200, [
            'days' => 7,
            'series' => [['date' => '2026-03-01', 'engagements' => 10, 'impressions' => 100, 'posts' => 1]],
        ]);
        $this->transport->push(200, ['data' => [[
            'rank' => 1,
            'post_id' => 'p_1',
            'source' => 'fopost',
            'preview' => 'Hello',
            'platforms' => [['platform' => 'bluesky', 'username' => 'yourbrand', 'url' => null, 'removed' => false]],
            'labels' => [],
            'metrics' => ['engagements' => 40, 'impressions' => 900],
        ]]]);

        $analytics = $this->client()->analytics();
        $overview = $analytics->overview(days: 30, workspaceId: 'w_1');
        $series = $analytics->timeSeries(from: '2026-03-01', to: '2026-03-07', accountId: 'a_1');
        $top = $analytics->topPosts(limit: 5, sort: 'engagements', label: 'l_1');

        $this->assertSame(
            'https://api.fopost.com/v1/analytics/overview?days=30&workspace_id=w_1',
            $this->transport->requests[0]['url'],
        );
        $this->assertSame(1200, $overview->totalFollowers);
        $this->assertSame(0.05, $overview->engagementRate);
        $this->assertSame(['followers' => 2.5, 'posts' => null], $overview->deltas);
        $this->assertSame(3, $overview->todayStats['follower_change']);
        $this->assertSame(198, $overview->accounts[0]->history[0]['followers']);

        $this->assertSame(
            'https://api.fopost.com/v1/analytics/time-series?from=2026-03-01&to=2026-03-07&account_id=a_1',
            $this->transport->requests[1]['url'],
        );
        $this->assertSame(7, $series->days);
        $this->assertSame(100, $series->series[0]->impressions);

        $this->assertSame(
            'https://api.fopost.com/v1/analytics/top-posts?limit=5&sort=engagements&label=l_1',
            $this->transport->last()['url'],
        );
        $this->assertSame('p_1', $top[0]->postId);
        $this->assertSame(40, $top[0]->metrics['engagements']);
        $this->assertFalse($top[0]->platforms[0]['removed']);
    }

    public function testLabelsTableStreakDemographicsAndInboxAreRead(): void
    {
        $this->transport->push(200, ['data' => [[
            'label_id' => 'l_1',
            'name' => 'Launch',
            'color' => '#0070f3',
            'post_count' => 4,
            'engagements' => 80,
            'engagement_rate' => 0.1,
            'follower_delta' => null,
        ]]]);
        $this->transport->push(200, [
            'data' => [[
                'post_id' => 'p_1',
                'preview' => 'Hello',
                'status' => 'published',
                'platforms' => [
                    ['platform' => 'bluesky', 'username' => 'y', 'url' => null, 'delivery_status' => 'published'],
                ],
                'delivery_summary' => ['total' => 1, 'published' => 1, 'failed' => 0, 'pending' => 0],
            ]],
            'meta' => ['page' => 2, 'per_page' => 10, 'total' => 11, 'total_pages' => 2],
            'summary' => ['published' => 11, 'total' => 11],
        ]);
        $this->transport->push(200, ['streak' => [['date' => '2026-03-01', 'count' => 2, 'published_count' => 2]]]);
        $this->transport->push(200, [
            'audience' => 'followers',
            'dimensions' => ['age' => [['key' => '25-34', 'value' => 40, 'share' => 0.4]], 'gender' => []],
            'contributing_accounts' => [['account_id' => 'a_1', 'platform' => 'instagram', 'username' => 'y']],
            'unsupported_accounts' => [],
        ]);
        $this->transport->push(200, [
            'totals' => ['received' => 30, 'sent' => 12, 'answered' => 10, 'open' => 5, 'conversations' => 8,
                'median_response_minutes' => 42.5],
            'deltas' => ['received' => -10],
            'series' => [['date' => '2026-03-01', 'received' => 3, 'sent' => 1, 'answered' => 1]],
            'by_type' => [['type' => 'comment', 'count' => 20]],
            'heatmap' => [['weekday' => 1, 'hour' => 9, 'count' => 4]],
            'time_zone' => 'Europe/Zagreb',
        ]);

        $analytics = $this->client()->analytics();
        $labels = $analytics->labels(days: 90);
        $table = $analytics->postsTable(2, 10, workspaceId: 'w_1');
        $streak = $analytics->postingStreak('w_1');
        $demographics = $analytics->demographics('followers', accountId: 'a_1');
        $inbox = $analytics->inbox(days: 30, tz: 'Europe/Zagreb');

        $this->assertSame('https://api.fopost.com/v1/analytics/labels?days=90', $this->transport->requests[0]['url']);
        $this->assertSame(4, $labels[0]->postCount);
        $this->assertNull($labels[0]->followerDelta);

        $this->assertSame(
            'https://api.fopost.com/v1/analytics/posts-table?page=2&per_page=10&workspace_id=w_1',
            $this->transport->requests[1]['url'],
        );
        $this->assertSame(2, $table->meta->currentPage);
        $this->assertSame(2, $table->totalPages);
        $this->assertSame('published', $table->rows[0]->platforms[0]['delivery_status']);
        $this->assertSame(11, $table->summary['total']);

        $this->assertSame(
            'https://api.fopost.com/v1/analytics/posting-streak?workspace_id=w_1',
            $this->transport->requests[2]['url'],
        );
        $this->assertSame(2, $streak[0]->publishedCount);

        $this->assertSame(
            'https://api.fopost.com/v1/analytics/demographics?audience=followers&account_id=a_1',
            $this->transport->requests[3]['url'],
        );
        $this->assertSame(0.4, $demographics->age[0]->share);
        $this->assertSame('instagram', $demographics->contributingAccounts[0]['platform']);

        $this->assertSame(
            'https://api.fopost.com/v1/analytics/inbox?days=30&tz=Europe%2FZagreb',
            $this->transport->last()['url'],
        );
        $this->assertSame(42.5, $inbox->medianResponseMinutes);
        $this->assertSame(['received' => -10.0], $inbox->deltas);
        $this->assertSame(20, $inbox->byType[0]['count']);
        $this->assertSame('Europe/Zagreb', $inbox->timeZone);
    }
}
