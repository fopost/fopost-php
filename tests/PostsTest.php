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

    public function testCreateSendsTheQueueSchedule(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'p_1', 'status' => 'scheduled']]);

        $this->client()->posts()->create(workspaceId: 'w_1', content: 'Hi', accounts: ['a_1'], schedule: 'queue');

        $body = $this->transport->lastJson();
        $this->assertSame('queue', $body['schedule']);
        $this->assertArrayNotHasKey('schedule_at', $body);
    }

    public function testUpdateSendsTheQueueSchedule(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'p_1', 'status' => 'scheduled']]);

        $this->client()->posts()->update('p_1', schedule: 'queue');

        $this->assertSame('PUT', $this->transport->last()['method']);
        $this->assertSame(['schedule' => 'queue'], $this->transport->lastJson());
    }

    public function testUnpublishTakesThePostDown(): void
    {
        $this->transport->push(200, [
            'post_status' => 'unpublished',
            'deliveries' => [['account_id' => 'a_1', 'platform' => 'bluesky', 'removed' => true]],
        ]);

        $result = $this->client()->posts()->unpublish('p_1');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/posts/p_1/unpublish', $this->transport->last()['url']);
        $this->assertSame('unpublished', $result->postStatus);
        $this->assertTrue($result->deliveries[0]->removed);
        $this->assertSame('bluesky', $result->deliveries[0]->platform);
    }

    public function testBulkImportSendsTheCsvAsMultipart(): void
    {
        $this->transport->push(200, [
            'total_rows' => 2,
            'valid_rows' => 1,
            'invalid_rows' => 1,
            'max_rows' => 500,
            'rows' => [['row' => 2, 'content_preview' => 'Hi', 'errors' => ['unknown account']]],
        ]);
        $this->transport->push(200, [
            'batch_id' => 'b_1',
            'created' => 1,
            'held_for_approval' => false,
            'posts' => [['id' => 'p_1', 'schedule_at' => '2026-03-01T09:00:00Z']],
        ]);
        $this->transport->push(200, ['deleted' => 1]);

        $csv = ['filename' => 'posts.csv', 'contents' => "text\nHi\n", 'mimeType' => 'text/csv'];
        $check = $this->client()->posts()->validateBulkImport('w_1', $csv);
        $commit = $this->client()->posts()->commitBulkImport('w_1', $csv);
        $rollback = $this->client()->posts()->rollbackBulkImport('b_1');

        $first = $this->transport->requests[0];
        $this->assertSame('POST', $first['method']);
        $this->assertSame('https://api.fopost.com/v1/posts/bulk-import/validate', $first['url']);
        $this->assertStringStartsWith('multipart/form-data; boundary=', $first['headers']['Content-Type']);
        $this->assertStringContainsString('name="workspace_id"' . "\r\n\r\nw_1", (string) $first['body']);
        $this->assertStringContainsString('name="file"; filename="posts.csv"', (string) $first['body']);
        $this->assertSame(1, $check->invalidRows);
        $this->assertSame(['unknown account'], $check->rows[0]->errors);

        $this->assertSame('https://api.fopost.com/v1/posts/bulk-import/commit', $this->transport->requests[1]['url']);
        $this->assertSame('b_1', $commit->batchId);
        $this->assertSame('2026-03-01', $commit->posts[0]->scheduleAt?->format('Y-m-d'));

        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/posts/bulk-import/b_1', $this->transport->last()['url']);
        $this->assertSame(1, $rollback->deleted);
    }
}
