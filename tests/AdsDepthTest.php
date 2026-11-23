<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class AdsDepthTest extends TestCase
{
    public function testBulkStatusReturnsOneResultPerObject(): void
    {
        $this->transport->push(200, ['data' => [
            ['id' => '120', 'level' => 'campaign', 'ok' => true, 'error' => null],
            ['id' => '122', 'level' => 'ad', 'ok' => false, 'error' => 'Not found'],
        ]]);

        $results = $this->client()->ads()->bulkSetStatus('w_1', 'conn_1', 'paused', [
            ['id' => '120', 'level' => 'campaign'],
            ['id' => '122', 'level' => 'ad'],
        ]);

        $this->assertSame('https://api.fopost.com/v1/ads/status', $this->transport->last()['url']);
        $this->assertSame('paused', $this->transport->lastJson()['status']);
        $this->assertTrue($results[0]->ok);
        $this->assertSame('Not found', $results[1]->error);
    }

    public function testInsightsSendTheRangeBreakdownAndDailyFlag(): void
    {
        $this->transport->push(200, ['data' => [
            'objectId' => '120',
            'currency' => 'USD',
            'since' => '2026-09-01',
            'until' => '2026-09-07',
            'breakdownBy' => 'age',
            'totals' => ['impressions' => 1000, 'clicks' => 20, 'spendMinor' => 500, 'ctr' => 2.0, 'leads' => 3],
            'breakdown' => [['key' => '25-34', 'metrics' => ['impressions' => 600]]],
            'timeline' => [['date' => '2026-09-01', 'metrics' => ['impressions' => 150]]],
        ]]);

        $report = $this->client()->ads()->insights(
            'conn_1',
            '120',
            '2026-09-01',
            '2026-09-07',
            breakdown: 'age',
            daily: true,
            workspaceId: 'w_1',
        );

        $this->assertSame(
            'https://api.fopost.com/v1/ads/insights?workspace_id=w_1&connection_id=conn_1&object_id=120'
                . '&since=2026-09-01&until=2026-09-07&breakdown=age&daily=true',
            $this->transport->last()['url'],
        );
        $this->assertSame(2.0, $report->totals?->ctr);
        $this->assertSame(3, $report->totals?->leads);
        $this->assertSame('25-34', $report->breakdown[0]->key);
        $this->assertSame(600, $report->breakdown[0]->metrics->impressions);
        $this->assertSame('2026-09-01', $report->timeline[0]->date);

        $this->transport->push(200, ['data' => ['objectId' => 'ad_1', 'totals' => null]]);
        $report = $this->client()->ads()->adInsights('ad_1', 'w_1', '2026-09-01', '2026-09-07', daily: false);
        $this->assertSame(
            'https://api.fopost.com/v1/ads/ad_1/insights?workspace_id=w_1&since=2026-09-01&until=2026-09-07'
                . '&daily=false',
            $this->transport->last()['url'],
        );
        $this->assertNull($report->totals);
    }

    public function testLeadsFeedPagesByCursor(): void
    {
        $this->transport->push(200, ['data' => [
            'leads' => [[
                'id' => '0b6c',
                'leadId' => '900',
                'pageId' => '555',
                'formId' => 'f_1',
                'isOrganic' => true,
                'fields' => [['name' => 'email', 'values' => ['lead@example.invalid']]],
                'submittedAt' => '2026-09-18T10:00:00Z',
            ]],
            'nextCursor' => 'cur_2',
        ]]);

        $page = $this->client()->ads()->leadsFeed('w_1', formId: 'f_1', limit: 50);
        $this->assertSame(
            'https://api.fopost.com/v1/ads/leads?workspace_id=w_1&form_id=f_1&limit=50',
            $this->transport->last()['url'],
        );
        $this->assertSame('900', $page->leads[0]->leadId);
        $this->assertTrue($page->leads[0]->isOrganic);
        $this->assertSame('2026-09-18', $page->leads[0]->submittedAt?->format('Y-m-d'));
        $this->assertSame('cur_2', $page->nextCursor);

        $this->transport->push(200, ['data' => ['leads' => [], 'nextCursor' => null]]);
        $next = $this->client()->ads()->leadsFeed('w_1', formId: 'f_1', cursor: $page->nextCursor, limit: 50);
        $this->assertSame(
            'https://api.fopost.com/v1/ads/leads?workspace_id=w_1&form_id=f_1&cursor=cur_2&limit=50',
            $this->transport->last()['url'],
        );
        $this->assertNull($next->nextCursor);
    }

    public function testAudienceUsersAndLeadPages(): void
    {
        $this->transport->push(200, ['data' => ['added' => 2]]);
        $emails = ['a@example.invalid', 'b@example.invalid'];
        $added = $this->client()->ads()->addAudienceUsers('aud_1', 'w_1', 'conn_1', $emails);
        $this->assertSame(
            'https://api.fopost.com/v1/ads/audiences/aud_1/users?workspace_id=w_1&connection_id=conn_1',
            $this->transport->last()['url'],
        );
        $this->assertSame(2, $added);

        $this->transport->push(201, ['data' => ['pageId' => '555', 'backfilled' => 7]]);
        $this->assertSame(7, $this->client()->ads()->subscribeLeadPage('w_1', 'conn_1', '555'));
        $this->assertSame(
            ['workspaceId' => 'w_1', 'connectionId' => 'conn_1', 'pageId' => '555'],
            $this->transport->lastJson(),
        );
    }

    public function testCreateSendsUrlTags(): void
    {
        $this->transport->push(201, ['data' => [
            'id' => 'ad_1',
            'kind' => 'ad',
            'name' => 'Ad',
            'goal' => 'traffic',
            'status' => 'paused',
            'creative' => ['text' => 'Hi', 'urlTags' => 'utm_source=meta'],
        ]]);

        $ad = $this->client()->ads()->create(
            workspaceId: 'w_1',
            connectionId: 'conn_1',
            adAccountId: 'act_123',
            pageId: '555',
            name: 'Ad',
            goal: 'traffic',
            budget: ['minor' => 1000, 'type' => 'daily'],
            targeting: ['countries' => ['US']],
            text: 'Hi',
            urlTags: 'utm_source=meta',
        );

        $this->assertSame('utm_source=meta', $this->transport->lastJson()['urlTags']);
        $this->assertSame('utm_source=meta', $ad->creative['urlTags'] ?? null);
    }
}
