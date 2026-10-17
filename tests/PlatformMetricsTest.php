<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\ApiException;

final class PlatformMetricsTest extends TestCase
{
    public function testPlatformMetricsAsksForRawAndParsesTheSet(): void
    {
        $this->transport->push(200, ['data' => [
            'platform' => 'facebook',
            'account' => [
                'fetched_at' => '2026-09-20T02:00:00.000Z',
                'metrics' => [
                    [
                        'key' => 'page_daily_video_ad_break_earnings',
                        'label' => 'Ad Break Earnings',
                        'kind' => 'currency_usd',
                        'value' => 42.15,
                    ],
                    [
                        'key' => 'page_impressions_paid',
                        'label' => 'Paid Impressions',
                        'kind' => 'count',
                        'value' => 1500,
                    ],
                ],
            ],
            'post' => [
                'external_post_id' => '123_456',
                'fetched_at' => '2026-09-20T02:00:00.000Z',
                'metrics' => [],
            ],
        ]]);

        $metrics = $this->client()->accounts()->platformMetrics('a_1');

        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a_1/insights?raw=true',
            $this->transport->last()['url'],
        );
        $this->assertSame('facebook', $metrics->platform);
        $this->assertSame('2026-09-20T02:00:00.000Z', $metrics->account->fetchedAt);
        $this->assertCount(2, $metrics->account->metrics);
        $this->assertSame('page_daily_video_ad_break_earnings', $metrics->account->metrics[0]->key);
        $this->assertSame('currency_usd', $metrics->account->metrics[0]->kind);
        $this->assertSame(42.15, $metrics->account->metrics[0]->value);
        $this->assertSame('123_456', $metrics->post->externalPostId);
        $this->assertSame([], $metrics->post->metrics);
    }

    public function testASeriesValueSurvivesAsAList(): void
    {
        $this->transport->push(200, ['data' => [
            'platform' => 'youtube',
            'account' => [
                'fetched_at' => null,
                'metrics' => [[
                    'key' => 'daily_views',
                    'label' => 'Views by Day',
                    'kind' => 'series',
                    'value' => [['day' => '2026-09-19', 'views' => 600]],
                ]],
            ],
            'post' => ['external_post_id' => null, 'fetched_at' => null, 'metrics' => []],
        ]]);

        $metrics = $this->client()->accounts()->platformMetrics('a_1');

        $this->assertSame(
            [['day' => '2026-09-19', 'views' => 600]],
            $metrics->account->metrics[0]->value,
        );
        $this->assertNull($metrics->account->fetchedAt);
    }

    public function testAPendingMetricGrantThrows(): void
    {
        $this->transport->push(503, [
            'error' => 'platform_metrics_unavailable',
            'message' => 'google-business metrics are not available on this deployment yet.',
        ]);

        try {
            $this->client(1)->accounts()->platformMetrics('a_1');
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            $this->assertSame(503, $e->status);
            $this->assertSame('platform_metrics_unavailable', $e->errorCode);
        }
    }
}
