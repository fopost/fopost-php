<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class KnowledgeTest extends TestCase
{
    public function testListParsesCamelCaseFieldsAndSendsTheWorkspaceFilter(): void
    {
        $this->transport->push(200, ['data' => [[
            'id' => 'know_1',
            'kind' => 'url',
            'title' => 'Refund policy',
            'status' => 'ready',
            'statusMessage' => null,
            'url' => 'https://yourbrand.com/help/refunds',
            'mediaId' => null,
            'brandVoiceId' => null,
            'chunkCount' => 3,
            'content' => null,
            'lastSyncedAt' => '2026-09-20T00:00:00Z',
            'createdAt' => '2026-09-19T00:00:00Z',
            'updatedAt' => '2026-09-20T00:00:00Z',
        ]]]);

        $sources = $this->client()->knowledge()->list(workspaceId: 'w_1');

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/knowledge/sources?workspace_id=w_1',
            $this->transport->last()['url'],
        );
        $this->assertCount(1, $sources);
        $this->assertSame('know_1', $sources[0]->id);
        $this->assertSame('ready', $sources[0]->status);
        $this->assertSame(3, $sources[0]->chunkCount);
        $this->assertSame('2026-09-20', $sources[0]->lastSyncedAt?->format('Y-m-d'));
    }

    public function testCreateSendsASnakeCaseBody(): void
    {
        $this->transport->push(200, ['data' => [
            'id' => 'know_1',
            'kind' => 'file',
            'title' => 'Price list',
            'status' => 'pending',
        ]]);

        $this->client()->knowledge()->create(
            kind: 'file',
            title: 'Price list',
            mediaId: 'media_1',
            brandVoiceId: 'brand_1',
            workspaceId: 'w_1',
        );

        $last = $this->transport->last();
        $this->assertSame('POST', $last['method']);
        $this->assertSame('https://api.fopost.com/v1/knowledge/sources', $last['url']);
        $this->assertSame([
            'kind' => 'file',
            'title' => 'Price list',
            'media_id' => 'media_1',
            'brand_voice_id' => 'brand_1',
            'workspace_id' => 'w_1',
        ], json_decode((string) $last['body'], true));
    }

    public function testSearchPassesTopKAndParsesMatches(): void
    {
        $this->transport->push(200, ['data' => [[
            'sourceId' => 'know_1',
            'sourceTitle' => 'Refund policy',
            'sourceKind' => 'url',
            'sourceUrl' => 'https://yourbrand.com/help/refunds',
            'text' => 'We refund within 30 days.',
            'score' => 0.82,
        ]]]);

        $matches = $this->client()->knowledge()->search('how long do refunds take?', topK: 3);

        $this->assertSame(
            'https://api.fopost.com/v1/knowledge/search?q=how+long+do+refunds+take%3F&top_k=3',
            $this->transport->last()['url'],
        );
        $this->assertCount(1, $matches);
        $this->assertSame('Refund policy', $matches[0]->sourceTitle);
        $this->assertEqualsWithDelta(0.82, $matches[0]->score, 0.0001);
    }

    public function testSyncPostsToTheSourcesSyncPath(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'know_1', 'status' => 'pending']]);

        $this->client()->knowledge()->sync('know_1');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/knowledge/sources/know_1/sync',
            $this->transport->last()['url'],
        );
    }
}
