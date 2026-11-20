<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\NotFoundException;
use Fopost\Sdk\Exception\PaymentRequiredException;

final class DesignsTest extends TestCase
{
    private const DESIGN = [
        'id' => 'd_1',
        'name' => 'Launch story',
        'kind' => 'story',
        'width' => 1080,
        'height' => 1920,
        'document' => ['pages' => [['layers' => []]]],
        'thumbnail_media_id' => 'm_9',
        'thumbnail_url' => 'https://api.fopost.com/v1/media/m_9/file',
        'created_at' => '2026-11-20T10:00:00Z',
        'updated_at' => '2026-11-20T11:00:00Z',
    ];

    private const VERSION = ['id' => 'v_1', 'version' => 3, 'created_at' => '2026-11-20T11:00:00Z'];

    public function testListSendsTheFiltersAndMapsRows(): void
    {
        $row = self::DESIGN;
        unset($row['document']);
        $this->transport->push(200, ['data' => [$row]]);

        $designs = $this->client()->designs()->list('w_1', kind: 'story', limit: 10, offset: 20);

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/designs?workspace_id=w_1&kind=story&limit=10&offset=20',
            $this->transport->last()['url'],
        );
        $this->assertCount(1, $designs);
        $this->assertSame('d_1', $designs[0]->id);
        $this->assertSame('story', $designs[0]->kind);
        $this->assertSame(1080, $designs[0]->width);
        $this->assertNull($designs[0]->document);
        $this->assertSame('m_9', $designs[0]->thumbnailMediaId);
        $this->assertSame('2026-11-20T10:00:00+00:00', $designs[0]->createdAt?->format('c'));
    }

    public function testCreatePostsSnakeCaseFields(): void
    {
        $this->transport->push(201, ['data' => self::DESIGN]);

        $design = $this->client()->designs()->create(
            'w_1',
            'story',
            name: 'Launch story',
            document: ['pages' => []],
            brandVoiceId: 'bv_1',
        );

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/designs', $this->transport->last()['url']);
        $this->assertSame([
            'workspace_id' => 'w_1',
            'name' => 'Launch story',
            'kind' => 'story',
            'document' => ['pages' => []],
            'brand_voice_id' => 'bv_1',
        ], $this->transport->lastJson());
        $this->assertSame(['pages' => [['layers' => []]]], $design->document);
    }

    public function testCreateKeepsAnEmptyObjectDocumentAsAnObject(): void
    {
        $this->transport->push(201, ['data' => self::DESIGN]);

        $this->client()->designs()->create('w_1', 'post', document: (object) ['data' => (object) []]);

        $this->assertStringContainsString('"document":{"data":{}}', (string) $this->transport->last()['body']);
    }

    public function testGetReadsOneDesign(): void
    {
        $this->transport->push(200, ['data' => self::DESIGN]);

        $design = $this->client()->designs()->get('d_1');

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/designs/d_1', $this->transport->last()['url']);
        $this->assertSame('Launch story', $design->name);
        $this->assertSame('https://api.fopost.com/v1/media/m_9/file', $design->thumbnailUrl);
    }

    public function testGetMapsAMissingDesignToNotFound(): void
    {
        $this->transport->push(404, ['error' => 'not_found', 'message' => 'Design not found']);

        $this->expectException(NotFoundException::class);
        $this->client()->designs()->get('d_404');
    }

    public function testUpdatePatchesOnlyThePassedFieldsAndReadsTheVersion(): void
    {
        $this->transport->push(200, ['data' => self::DESIGN, 'version' => self::VERSION]);

        $revision = $this->client()->designs()->update('d_1', name: 'Renamed', createVersion: true);

        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/designs/d_1', $this->transport->last()['url']);
        $this->assertSame(['name' => 'Renamed', 'create_version' => true], $this->transport->lastJson());
        $this->assertSame('d_1', $revision->design->id);
        $this->assertSame(3, $revision->version?->version);
        $this->assertNull($revision->version?->document);
    }

    public function testAnEmptyUpdateSendsAnObject(): void
    {
        $this->transport->push(200, ['data' => self::DESIGN, 'version' => null]);

        $revision = $this->client()->designs()->update('d_1');

        $this->assertSame('{}', $this->transport->last()['body']);
        $this->assertNull($revision->version);
    }

    public function testDeleteSendsADelete(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'd_1', 'deleted' => true]]);

        $this->client()->designs()->delete('d_1');

        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/designs/d_1', $this->transport->last()['url']);
    }

    public function testVersionsAsksForDocumentsOnlyWhenTold(): void
    {
        $this->transport->push(200, ['data' => [self::VERSION]]);
        $this->transport->push(200, ['data' => [self::VERSION + ['document' => ['pages' => []]]]]);

        $plain = $this->client()->designs()->versions('d_1');
        $this->assertSame('https://api.fopost.com/v1/designs/d_1/versions', $this->transport->last()['url']);
        $this->assertSame('v_1', $plain[0]->id);
        $this->assertNull($plain[0]->document);

        $full = $this->client()->designs()->versions('d_1', withDocument: true);
        $this->assertSame(
            'https://api.fopost.com/v1/designs/d_1/versions?include=document',
            $this->transport->last()['url'],
        );
        $this->assertSame(['pages' => []], $full[0]->document);
    }

    public function testRestoreVersionPostsToTheVersion(): void
    {
        $this->transport->push(200, ['data' => self::DESIGN, 'version' => self::VERSION]);

        $revision = $this->client()->designs()->restoreVersion('d_1', 'v_1');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/designs/d_1/versions/v_1/restore',
            $this->transport->last()['url'],
        );
        $this->assertSame('d_1', $revision->design->id);
        $this->assertSame('v_1', $revision->version?->id);
    }

    public function testDuplicateSendsTheNameOrAnEmptyObject(): void
    {
        $this->transport->push(201, ['data' => ['id' => 'd_2'] + self::DESIGN]);
        $this->transport->push(201, ['data' => ['id' => 'd_3'] + self::DESIGN]);

        $copy = $this->client()->designs()->duplicate('d_1', 'Copy');
        $this->assertSame('https://api.fopost.com/v1/designs/d_1/duplicate', $this->transport->last()['url']);
        $this->assertSame(['name' => 'Copy'], $this->transport->lastJson());
        $this->assertSame('d_2', $copy->id);

        $this->client()->designs()->duplicate('d_1');
        $this->assertSame('{}', $this->transport->last()['body']);
    }

    public function testExportMapsTheRenderedFiles(): void
    {
        $this->transport->push(200, ['data' => [
            'format' => 'mp4',
            'files' => [[
                'media_id' => 'm_1',
                'page_index' => 0,
                'mime_type' => 'video/mp4',
                'preview_url' => 'https://api.fopost.com/v1/media/m_1/file',
            ]],
        ]]);

        $export = $this->client()->designs()->export('d_1', 'mp4', pageIndexes: [0], durationSec: 10);

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/designs/d_1/export', $this->transport->last()['url']);
        $this->assertSame(
            ['format' => 'mp4', 'page_indexes' => [0], 'duration_sec' => 10],
            $this->transport->lastJson(),
        );
        $this->assertSame('mp4', $export->format);
        $this->assertSame('m_1', $export->files[0]->mediaId);
        $this->assertSame(0, $export->files[0]->pageIndex);
        $this->assertSame('video/mp4', $export->files[0]->mimeType);
    }

    public function testBulkSendsTheRowsAndReturnsTheCopies(): void
    {
        $this->transport->push(201, ['data' => [self::DESIGN, ['id' => 'd_2'] + self::DESIGN]]);

        $designs = $this->client()->designs()->bulk(
            'd_1',
            [['name' => 'Ana', 'headline' => 'Hi'], ['name' => 'Ben', 'headline' => 'Yo']],
            nameColumn: 'name',
        );

        $this->assertSame('https://api.fopost.com/v1/designs/d_1/bulk', $this->transport->last()['url']);
        $this->assertSame([
            'rows' => [['name' => 'Ana', 'headline' => 'Hi'], ['name' => 'Ben', 'headline' => 'Yo']],
            'name_column' => 'name',
        ], $this->transport->lastJson());
        $this->assertSame(['d_1', 'd_2'], array_map(static fn ($d) => $d->id, $designs));
    }

    public function testPostsMapsMetrics(): void
    {
        $this->transport->push(200, ['data' => [[
            'id' => 'p_1',
            'status' => 'published',
            'title' => null,
            'text' => 'Out now',
            'schedule_at' => null,
            'published_at' => '2026-11-19T09:00:00Z',
            'platforms' => ['instagram', 'tiktok'],
            'engagement' => 42,
            'views' => 1000,
        ]]]);

        $posts = $this->client()->designs()->posts('d_1');

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/designs/d_1/posts', $this->transport->last()['url']);
        $this->assertSame('published', $posts[0]->status);
        $this->assertSame(['instagram', 'tiktok'], $posts[0]->platforms);
        $this->assertSame(42, $posts[0]->engagement);
        $this->assertSame(1000, $posts[0]->views);
        $this->assertNull($posts[0]->scheduleAt);
    }

    public function testTextToSlidesMapsTheOutline(): void
    {
        $this->transport->push(200, ['data' => [
            'cover' => ['title' => 'Five ideas', 'subtitle' => 'For launch week'],
            'slides' => [['heading' => 'One', 'body' => 'Start small']],
            'cta' => ['heading' => 'Follow', 'body' => 'More every week'],
            'brand' => ['colors' => ['#111111'], 'fonts' => [], 'logo_media_id' => null],
        ]]);

        $outline = $this->client()->designs()->textToSlides('w_1', text: 'Long text', maxSlides: 6);

        $this->assertSame('https://api.fopost.com/v1/designs/text-to-slides', $this->transport->last()['url']);
        $this->assertSame(
            ['workspace_id' => 'w_1', 'text' => 'Long text', 'max_slides' => 6],
            $this->transport->lastJson(),
        );
        $this->assertSame('Five ideas', $outline->cover['title']);
        $this->assertSame('Start small', $outline->slides[0]['body']);
        $this->assertSame('Follow', $outline->cta['heading']);
        $this->assertSame(['#111111'], $outline->brand['colors']);
    }

    public function testTextToSlidesSurfacesAnEmptyBalance(): void
    {
        $this->transport->push(402, [
            'error' => 'insufficient_credits',
            'message' => 'Your AI balance is empty.',
            'upgrade_url' => '/settings/billing',
        ]);

        try {
            $this->client()->designs()->textToSlides('w_1', postId: 'p_1');
            $this->fail('expected the 402 to throw');
        } catch (PaymentRequiredException $e) {
            $this->assertSame('insufficient_credits', $e->getErrorCode());
            $this->assertSame('/settings/billing', $e->getUpgradeUrl());
        }
    }

    public function testFromPostSendsTheOptions(): void
    {
        $this->transport->push(201, ['data' => self::DESIGN]);

        $design = $this->client()->designs()->fromPost(
            'w_1',
            postId: 'p_1',
            style: 'quote',
            preset: 'instagram-portrait',
            accountId: 'a_1',
            carousel: false,
            name: 'Quote card',
        );

        $this->assertSame('https://api.fopost.com/v1/designs/from-post', $this->transport->last()['url']);
        $this->assertSame([
            'workspace_id' => 'w_1',
            'post_id' => 'p_1',
            'style' => 'quote',
            'preset' => 'instagram-portrait',
            'account_id' => 'a_1',
            'carousel' => false,
            'name' => 'Quote card',
        ], $this->transport->lastJson());
        $this->assertSame('d_1', $design->id);
    }
}
