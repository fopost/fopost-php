<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class ActivityTest extends TestCase
{
    public function testReadsTheAuditLogAndKeepsTheCursor(): void
    {
        $this->transport->push(200, [
            'data' => [[
                'id' => 'evt_1',
                'workspace_id' => 'w_1',
                'kind' => 'security',
                'ref_type' => 'member_removed',
                'ref_id' => 'u_2',
                'summary' => 'Removed sam@example.com',
                'actor' => ['type' => 'user', 'name' => 'Ada'],
                'time' => '2026-09-20T10:00:00Z',
            ]],
            'meta' => ['next_cursor' => '42'],
        ]);

        $page = $this->client()->activity()->list('w_1', 'security', limit: 1);

        $this->assertStringContainsString('kind=security', $this->transport->last()['url']);
        $this->assertStringContainsString('workspace_id=w_1', $this->transport->last()['url']);
        $this->assertSame('member_removed', $page->events[0]->refType);
        $this->assertSame('Ada', $page->events[0]->actor->name);
        $this->assertSame('42', $page->nextCursor);
    }

    public function testTheEndOfTheListIsANullCursor(): void
    {
        $this->transport->push(200, ['data' => [], 'meta' => ['next_cursor' => null]]);

        $page = $this->client()->activity()->list();

        $this->assertSame([], $page->events);
        $this->assertNull($page->nextCursor);
    }
}
