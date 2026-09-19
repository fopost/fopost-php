<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use DateTimeImmutable;
use Fopost\Sdk\Model\Post;

final class PostsTest extends TestCase
{
    public function testListReturnsAPageWithItsMeta(): void
    {
        $this->transport->push(200, [
            'data' => [
                ['id' => 'p_1', 'status' => 'draft', 'content' => [['text' => 'Hello']]],
                ['id' => 'p_2', 'status' => 'scheduled'],
            ],
            'meta' => ['current_page' => 1, 'per_page' => 30, 'total' => 2, 'last_page' => 1],
        ]);

        $page = $this->client()->posts()->list(workspaceId: 'w_1', status: 'draft');

        $this->assertStringContainsString('workspace_id=w_1', $this->transport->last()['url']);
        $this->assertStringContainsString('status=draft', $this->transport->last()['url']);
        $this->assertStringContainsString('per_page=30', $this->transport->last()['url']);
        $this->assertCount(2, $page);
        $this->assertSame('p_1', $page[0]?->id);
        $this->assertSame('Hello', $page[0]?->text());
        $this->assertSame(2, $page->meta->total);

        $ids = [];
        foreach ($page as $post) {
            $ids[] = $post->id;
        }
        $this->assertSame(['p_1', 'p_2'], $ids);
    }

    public function testIteratePagesStopsAtTheLastPage(): void
    {
        $this->transport->push(200, [
            'data' => [['id' => 'p_1', 'status' => 'draft']],
            'meta' => ['current_page' => 1, 'per_page' => 1, 'last_page' => 2],
        ]);
        $this->transport->push(200, [
            'data' => [['id' => 'p_2', 'status' => 'draft']],
            'meta' => ['current_page' => 2, 'per_page' => 1, 'last_page' => 2],
        ]);

        $ids = [];
        foreach ($this->client()->posts()->iterate(perPage: 1) as $post) {
            $ids[] = $post->id;
        }

        $this->assertSame(['p_1', 'p_2'], $ids);
        $this->assertSame(2, $this->transport->requestCount());
    }

    public function testGetUnwrapsABareResource(): void
    {
        $this->transport->push(200, ['id' => 'p_1', 'status' => 'published', 'schedule_at' => '2026-03-01T09:00:00Z']);

        $post = $this->client()->posts()->get('p_1');

        $this->assertSame('https://api.fopost.com/v1/posts/p_1', $this->transport->last()['url']);
        $this->assertInstanceOf(Post::class, $post);
        $this->assertSame('published', $post->status);
        $this->assertSame('2026-03-01 09:00', $post->scheduleAt?->format('Y-m-d H:i'));
    }

    public function testCreateNormalisesAStringBodyAndAccountObjects(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'p_1', 'status' => 'draft']]);

        $post = $this->client()->posts()->create(
            workspaceId: 'w_1',
            content: 'Hello from PHP',
            accounts: ['a_1', ['id' => 'a_2']],
            labels: ['l_1'],
        );

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/posts', $this->transport->last()['url']);
        $this->assertSame([
            'workspace_id' => 'w_1',
            'status' => 'draft',
            'content' => [['text' => 'Hello from PHP']],
            'accounts' => ['a_1', 'a_2'],
            'labels' => ['l_1'],
        ], $this->transport->lastJson());
        $this->assertSame('p_1', $post->id);
    }

    public function testCreateSendsTheAccountGroupId(): void
    {
        $this->transport->push(201, ['data' => ['id' => 'p_1', 'status' => 'draft']]);

        $this->client()->posts()->create(workspaceId: 'w_1', content: 'Hi', accountGroupId: 'g_1');

        $body = $this->transport->lastJson();
        $this->assertSame('g_1', $body['account_group_id']);
        $this->assertSame([], $body['accounts']);
    }

    public function testCreateSerialisesADateTimeSchedule(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'p_1', 'status' => 'scheduled']]);

        $this->client()->posts()->create(
            workspaceId: 'w_1',
            content: [['text' => 'One'], ['text' => 'Two']],
            accounts: ['a_1'],
            status: 'scheduled',
            scheduleAt: new DateTimeImmutable('2026-03-01T09:00:00+00:00'),
        );

        $body = $this->transport->lastJson();
        $this->assertSame('scheduled', $body['status']);
        $this->assertSame('2026-03-01T09:00:00Z', $body['schedule_at']);
        $this->assertSame([['text' => 'One'], ['text' => 'Two']], $body['content']);
    }

    public function testUpdateSendsOnlyTheFieldsGiven(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'p_1', 'status' => 'draft']]);

        $this->client()->posts()->update('p_1', title: 'New title');

        $this->assertSame('PUT', $this->transport->last()['method']);
        $this->assertSame(['title' => 'New title'], $this->transport->lastJson());
    }

    public function testUpdateCanClearAFieldWithNull(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'p_1', 'status' => 'draft']]);

        $this->client()->posts()->update('p_1', scheduleAt: null);

        $this->assertSame(['schedule_at' => null], $this->transport->lastJson());
    }

    public function testDeleteRemovesThePost(): void
    {
        $this->transport->push(204);

        $this->client()->posts()->delete('p_1');

        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/posts/p_1', $this->transport->last()['url']);
    }

    public function testPublishQueuesThePost(): void
    {
        $this->transport->push(200, ['data' => ['queued' => 2]]);

        $result = $this->client()->posts()->publish('p_1');

        $this->assertSame('https://api.fopost.com/v1/posts/p_1/publish', $this->transport->last()['url']);
        $this->assertSame(['queued' => 2], $result);
    }

    public function testScheduleSendsTheScheduleAt(): void
    {
        $this->transport->push(200, ['data' => ['status' => 'scheduled']]);

        $this->client()->posts()->schedule('p_1', new DateTimeImmutable('2026-03-01T09:00:00+00:00'));

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/posts/p_1/schedule', $this->transport->last()['url']);
        $this->assertSame(['schedule_at' => '2026-03-01T09:00:00Z'], $this->transport->lastJson());
    }

    public function testDeliveriesReturnsDeliveryModels(): void
    {
        $this->transport->push(200, ['data' => [
            ['id' => 'd_1', 'status' => 'published', 'platform' => 'bluesky', 'external_url' => 'https://x.test/1'],
        ]]);

        $deliveries = $this->client()->posts()->deliveries('p_1');

        $this->assertSame('https://api.fopost.com/v1/posts/p_1/deliveries', $this->transport->last()['url']);
        $this->assertSame('d_1', $deliveries[0]->id);
        $this->assertSame('https://x.test/1', $deliveries[0]->externalUrl);
    }
}
