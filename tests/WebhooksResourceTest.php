<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class WebhooksResourceTest extends TestCase
{
    public function testEndpointsAreListedCreatedUpdatedTestedAndDeleted(): void
    {
        $hook = [
            'id' => 'wh_1',
            'workspace_id' => 'w_1',
            'name' => 'Orders',
            'url' => 'https://yourbrand.com/hooks/fopost',
            'events' => ['post.published'],
            'header_names' => ['X-Token'],
            'active' => true,
            'last_triggered_at' => null,
            'failure_count' => 0,
            'created_at' => '2026-03-01T09:00:00Z',
        ];
        $this->transport->push(200, ['data' => [$hook]]);
        $this->transport->push(201, $hook + ['secret' => 'whsec_once']);
        $this->transport->push(200, ['active' => false] + $hook);
        $this->transport->push(204);
        $this->transport->push(204);

        $webhooks = $this->client()->webhooks();
        $list = $webhooks->list();
        $created = $webhooks->create(
            'w_1',
            'https://yourbrand.com/hooks/fopost',
            ['post.published'],
            name: 'Orders',
            headers: ['X-Token' => 'abc'],
        );
        $updated = $webhooks->update('wh_1', active: false, headers: null);
        $webhooks->test('wh_1');
        $webhooks->delete('wh_1');

        $this->assertSame('https://api.fopost.com/v1/webhooks', $this->transport->requests[0]['url']);
        $this->assertSame(['X-Token'], $list[0]->headerNames);
        $this->assertNull($list[0]->secret);

        $this->assertSame('POST', $this->transport->requests[1]['method']);
        $this->assertSame([
            'workspaceId' => 'w_1',
            'url' => 'https://yourbrand.com/hooks/fopost',
            'events' => ['post.published'],
            'name' => 'Orders',
            'headers' => ['X-Token' => 'abc'],
        ], json_decode((string) $this->transport->requests[1]['body'], true));
        $this->assertSame('whsec_once', $created->secret);

        $this->assertSame('PUT', $this->transport->requests[2]['method']);
        $this->assertSame('https://api.fopost.com/v1/webhooks/wh_1', $this->transport->requests[2]['url']);
        $this->assertSame(
            ['active' => false, 'headers' => null],
            json_decode((string) $this->transport->requests[2]['body'], true),
        );
        $this->assertFalse($updated->active);

        $this->assertSame('POST', $this->transport->requests[3]['method']);
        $this->assertSame('https://api.fopost.com/v1/webhooks/wh_1/test', $this->transport->requests[3]['url']);
        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/webhooks/wh_1', $this->transport->last()['url']);
    }

    public function testDeliveriesAreListedReadAndRedelivered(): void
    {
        $delivery = [
            'id' => 'd_1',
            'webhook_id' => 'wh_1',
            'event' => 'post.published',
            'delivery_key' => 'dk_1',
            'attempt' => 2,
            'status' => 'failed',
            'response_status' => 500,
            'response_snippet' => 'boom',
            'duration_ms' => 120,
            'payload_hash' => 'abc',
            'next_retry_at' => '2026-03-01T09:10:00Z',
            'created_at' => '2026-03-01T09:00:00Z',
        ];
        $this->transport->push(200, ['data' => [$delivery]]);
        $this->transport->push(200, $delivery + ['payload' => ['event' => 'post.published']]);
        $this->transport->push(204);

        $webhooks = $this->client()->webhooks();
        $list = $webhooks->listDeliveries('wh_1', status: 'failed', limit: 10);
        $one = $webhooks->getDelivery('wh_1', 'd_1');
        $webhooks->redeliver('wh_1', 'd_1');

        $this->assertSame(
            'https://api.fopost.com/v1/webhooks/wh_1/deliveries?status=failed&limit=10',
            $this->transport->requests[0]['url'],
        );
        $this->assertSame(2, $list[0]->attempt);
        $this->assertSame(500, $list[0]->responseStatus);
        $this->assertNull($list[0]->payload);

        $this->assertSame('https://api.fopost.com/v1/webhooks/wh_1/deliveries/d_1', $this->transport->requests[1]['url']);
        $this->assertSame(['event' => 'post.published'], $one->payload);
        $this->assertSame('2026-03-01T09:10:00+00:00', $one->nextRetryAt?->format(DATE_ATOM));

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/webhooks/wh_1/deliveries/d_1/redeliver',
            $this->transport->last()['url'],
        );
    }
}
