<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class LogsTest extends TestCase
{
    public function testListReadsLogsMetaAndSummary(): void
    {
        $this->transport->push(200, [
            'data' => [[
                'id' => 31,
                'workspace_id' => 'w_1',
                'time' => '2026-03-01T09:00:00Z',
                'auth_method' => 'api_key',
                'method' => 'POST',
                'route' => '/v1/posts',
                'path' => '/v1/posts',
                'status_code' => 422,
                'duration_ms' => 40,
                'platform' => null,
                'message' => 'content is required',
            ]],
            'meta' => ['next_cursor' => '30', 'has_more' => true],
            'summary' => [
                'since' => '2026-02-22T00:00:00Z',
                'total' => 10,
                'errors' => 1,
                'buckets' => [['at' => '2026-03-01T00:00:00Z', 'total' => 10, 'errors' => 1]],
            ],
        ]);

        $page = $this->client()->logs()->list('w_1', days: 7, status: 'error', limit: 50, before: '40');

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/logs?workspace_id=w_1&days=7&status=error&limit=50&before=40',
            $this->transport->last()['url'],
        );
        $this->assertSame(422, $page->logs[0]->statusCode);
        $this->assertSame('/v1/posts', $page->logs[0]->route);
        $this->assertSame('30', $page->nextCursor);
        $this->assertTrue($page->hasMore);
        $this->assertSame(1, $page->errors);
        $this->assertSame(10, $page->buckets[0]->total);
    }
}
