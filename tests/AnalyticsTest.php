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
}
