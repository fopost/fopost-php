<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class AdsTrackingTest extends TestCase
{
    public function testIdempotentSendCarriesTheKeyAndReportsAReplay(): void
    {
        $this->transport->push(202, [
            'data' => ['id' => 'cb_1', 'accepted' => 1, 'eventCount' => 1, 'replayed' => false],
        ]);
        $result = $this->client()->ads()->sendConversions('w_1', 'conn_1', 'act_1', 'px_1', [[
            'eventName' => 'Purchase',
            'occurredAt' => '2026-10-18T10:04:00Z',
            'eventId' => 'order_77',
            'actionSource' => 'website',
            'email' => 'buyer@example.test',
            'valueMinor' => 4999,
            'currency' => 'USD',
        ]], 'order_77');

        $this->assertSame('cb_1', $result->id);
        $this->assertSame(1, $result->accepted);
        $this->assertFalse($result->replayed);
        $request = $this->transport->last();
        $this->assertSame('https://api.fopost.com/v1/ads/conversions/events', $request['url']);
        $this->assertSame('order_77', $request['headers']['Idempotency-Key']);
        $this->assertSame('fop_test_key', $request['headers']['X-API-Key']);
        $this->assertSame('order_77', $this->transport->lastJson()['events'][0]['eventId']);

        $this->transport->push(
            202,
            ['data' => ['id' => 'cb_1', 'accepted' => 1, 'eventCount' => 1, 'replayed' => true]],
            ['idempotency-replayed' => 'true'],
        );
        $replay = $this->client()->ads()->sendConversions('w_1', 'conn_1', 'act_1', 'px_1', [[
            'eventName' => 'Purchase',
            'occurredAt' => '2026-10-18T10:04:00Z',
        ]], 'order_77');
        $this->assertTrue($replay->replayed);

        $this->transport->push(202, [
            'data' => ['id' => 'cb_2', 'accepted' => 1, 'eventCount' => 1, 'replayed' => false],
        ]);
        $this->client()->ads()->uploadConversions('w_1', 'conn_1', 'act_1', 'px_1', [[
            'eventName' => 'Lead',
            'occurredAt' => '2026-10-18T10:04:00Z',
        ]], 'lead_3');
        $this->assertSame('lead_3', $this->transport->last()['headers']['Idempotency-Key']);
        $this->assertSame('https://api.fopost.com/v1/ads/conversions', $this->transport->last()['url']);
    }

    public function testStoredBatchesKeepIdentifiersAsHashes(): void
    {
        $this->transport->push(200, ['data' => [[
            'id' => 'cb_1',
            'workspaceId' => 'w_1',
            'connectionId' => 'conn_1',
            'adAccountId' => 'act_1',
            'pixelId' => 'px_1',
            'idempotencyKey' => 'order_77',
            'eventCount' => 1,
            'accepted' => 1,
            'events' => [[
                'eventName' => 'Purchase',
                'occurredAt' => '2026-10-18T10:04:00Z',
                'eventId' => 'order_77',
                'actionSource' => 'website',
                'emailHash' => 'ab12',
                'phoneHash' => null,
                'externalIdHash' => null,
                'valueMinor' => 4999,
                'currency' => 'USD',
                'orderId' => null,
            ]],
            'createdAt' => '2026-10-18T10:04:01Z',
        ]]]);
        $batches = $this->client()->ads()->listConversionEvents('w_1', 'conn_1', 10);

        $this->assertSame('order_77', $batches[0]->idempotencyKey);
        $this->assertSame('ab12', $batches[0]->events[0]->emailHash);
        $this->assertNull($batches[0]->events[0]->phoneHash);
        $this->assertSame(4999, $batches[0]->events[0]->valueMinor);
        $this->assertSame(
            'https://api.fopost.com/v1/ads/conversions/events?workspace_id=w_1&connection_id=conn_1&limit=10',
            $this->transport->last()['url'],
        );
    }

    public function testPixelReadsCarryTheAccountOnTheQueryString(): void
    {
        $this->transport->push(200, ['data' => [['id' => 'px_1', 'name' => 'Site']]]);
        $pixels = $this->client()->ads()->pixels('w_1', 'conn_1', 'act_1');
        $this->assertSame('Site', $pixels[0]->name);
        $this->assertSame(
            'https://api.fopost.com/v1/ads/pixels?workspace_id=w_1&connection_id=conn_1&ad_account_id=act_1',
            $this->transport->last()['url'],
        );

        $this->transport->push(200, ['data' => [
            'id' => 'px_1',
            'name' => 'Site',
            'code' => '<script>fbq()</script>',
            'createdAt' => '2026-01-01T00:00:00Z',
            'lastFiredAt' => null,
            'isUnavailable' => false,
            'dataUseSetting' => 'advertising_and_analytics',
        ]]);
        $pixel = $this->client()->ads()->pixel('px_1', 'w_1', 'conn_1', 'act_1');
        $this->assertSame('<script>fbq()</script>', $pixel->code);
        $this->assertSame('advertising_and_analytics', $pixel->dataUseSetting);
        $this->assertFalse($pixel->isUnavailable);
        $this->assertNull($pixel->lastFiredAt);
        $this->assertStringContainsString('/ads/pixels/px_1?workspace_id=w_1', $this->transport->last()['url']);

        $this->transport->push(200, ['data' => [
            'since' => '2026-09-20',
            'until' => '2026-10-18',
            'events' => [['eventName' => 'PageView', 'count' => 120]],
        ]]);
        $stats = $this->client()->ads()->pixelStats('px_1', 'w_1', 'conn_1', 'act_1');
        $this->assertSame(120, $stats->events[0]->count);
        $this->assertStringContainsString('/ads/pixels/px_1/stats?', $this->transport->last()['url']);

        $this->transport->push(200, ['data' => [[
            'eventName' => 'Purchase',
            'score' => 6.5,
            'matchKeys' => [['identifier' => 'em', 'coveragePercent' => 80]],
        ]]]);
        $quality = $this->client()->ads()->eventMatchQuality('px_1', 'w_1', 'conn_1', 'act_1');
        $this->assertSame(6.5, $quality[0]->score);
        $this->assertSame(80.0, $quality[0]->matchKeys[0]->coveragePercent);
        $this->assertStringContainsString('/ads/pixels/px_1/match-quality?', $this->transport->last()['url']);
    }

    public function testPixelWrites(): void
    {
        $this->transport->push(201, ['data' => ['id' => 'px_2', 'name' => 'Shop', 'isUnavailable' => false]]);
        $created = $this->client()->ads()->createPixel('w_1', 'conn_1', 'act_1', 'Shop');
        $this->assertSame('px_2', $created->id);
        $this->assertSame('Shop', $this->transport->lastJson()['name']);

        $this->transport->push(200, [
            'data' => ['id' => 'px_2', 'name' => 'Shop', 'dataUseSetting' => 'analytics_only'],
        ]);
        $this->client()->ads()->updatePixel('px_2', 'w_1', 'conn_1', 'act_1', dataUseSetting: 'analytics_only');
        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertArrayNotHasKey('name', $this->transport->lastJson());
        $this->assertSame('analytics_only', $this->transport->lastJson()['dataUseSetting']);

        $this->transport->push(204, null);
        $this->client()->ads()->sharePixel('px_2', 'w_1', 'conn_1', 'act_1', 'act_2');
        $this->assertSame('https://api.fopost.com/v1/ads/pixels/px_2/share', $this->transport->last()['url']);
        $this->assertSame('act_2', $this->transport->lastJson()['targetAdAccountId']);
    }

    public function testCustomConversionCreateSendsTheRuleAndReadsItBack(): void
    {
        $this->transport->push(201, ['data' => [
            'id' => 'cc_1',
            'name' => 'Checkout',
            'pixelId' => 'px_1',
            'eventType' => 'PURCHASE',
            'rule' => ['eventName' => 'PageView', 'urlContains' => '/thank-you'],
            'description' => null,
            'defaultValueMinor' => 2500,
            'isArchived' => false,
            'isUnavailable' => false,
            'createdAt' => '2026-10-18T10:00:00Z',
            'lastFiredAt' => null,
        ]]);
        $conversion = $this->client()->ads()->createCustomConversion(
            'w_1',
            'conn_1',
            'act_1',
            'px_1',
            'Checkout',
            'PURCHASE',
            ['urlContains' => '/thank-you'],
            defaultValueMinor: 2500,
        );

        $this->assertSame('cc_1', $conversion->id);
        $this->assertSame('PURCHASE', $conversion->eventType);
        $this->assertSame('/thank-you', $conversion->rule?->urlContains);
        $this->assertNull($conversion->rule?->urlEquals);
        $this->assertSame(2500, $conversion->defaultValueMinor);
        $this->assertFalse($conversion->isArchived);
        $body = $this->transport->lastJson();
        $this->assertSame('https://api.fopost.com/v1/ads/custom-conversions', $this->transport->last()['url']);
        $this->assertSame(['urlContains' => '/thank-you'], $body['rule']);
        $this->assertArrayNotHasKey('description', $body);

        $this->transport->push(200, [
            'data' => [['id' => 'cc_1', 'name' => 'Checkout', 'eventType' => 'PURCHASE', 'rule' => null]],
        ]);
        $list = $this->client()->ads()->customConversions('w_1', 'conn_1', 'act_1');
        $this->assertNull($list[0]->rule);
        $this->assertStringContainsString('/ads/custom-conversions?workspace_id=w_1', $this->transport->last()['url']);

        $this->transport->push(200, ['data' => ['id' => 'cc_1', 'name' => 'Checkout', 'eventType' => 'PURCHASE']]);
        $this->client()->ads()->customConversion('cc_1', 'w_1', 'conn_1', 'act_1');
        $this->assertStringContainsString('/ads/custom-conversions/cc_1?', $this->transport->last()['url']);

        $this->transport->push(200, ['data' => ['id' => 'cc_1', 'name' => 'Checkout 2', 'eventType' => 'PURCHASE']]);
        $updated = $this->client()->ads()->updateCustomConversion('cc_1', 'w_1', 'conn_1', 'act_1', name: 'Checkout 2');
        $this->assertSame('Checkout 2', $updated->name);
        $this->assertSame(
            ['workspaceId', 'connectionId', 'adAccountId', 'name'],
            array_keys($this->transport->lastJson()),
        );

        $this->transport->push(204, null);
        $this->client()->ads()->deleteCustomConversion('cc_1', 'w_1', 'conn_1', 'act_1');
        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('act_1', $this->transport->lastJson()['adAccountId']);
    }
}
