<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\ValidationException;

final class VideoEditsTest extends TestCase
{
    private const JOB = [
        'id' => 've_1',
        'status' => 'succeeded',
        'progress' => 1,
        'source_media_id' => 'm_1',
        'operations' => [['op' => 'trim', 'params' => ['startSec' => 0, 'endSec' => 10]]],
        'result_media_id' => 'm_2',
        'result_media' => [
            'id' => 'm_2',
            'preview_url' => 'https://api.fopost.com/v1/media/m_2/file',
            'mime_type' => 'video/mp4',
        ],
        'extra_outputs' => [['media_id' => 'm_3', 'label' => 'Subtitles', 'mime' => 'text/vtt', 'preview_url' => null]],
        'result' => null,
        'error' => null,
        'created_at' => '2026-11-20T10:00:00Z',
        'updated_at' => '2026-11-20T10:01:00Z',
        'completed_at' => '2026-11-20T10:01:00Z',
    ];

    public function testOpsListsTheOperations(): void
    {
        $this->transport->push(200, ['data' => [
            ['name' => 'trim', 'available' => true, 'uses_ai' => false],
            ['name' => 'subtitles', 'available' => true, 'uses_ai' => true],
        ]]);

        $ops = $this->client()->videoEdits()->ops();

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/video-edits/ops', $this->transport->last()['url']);
        $this->assertSame('trim', $ops[0]->name);
        $this->assertTrue($ops[0]->available);
        $this->assertFalse($ops[0]->usesAi);
        $this->assertTrue($ops[1]->usesAi);
    }

    public function testListSendsTheFilters(): void
    {
        $this->transport->push(200, ['data' => [self::JOB]]);

        $jobs = $this->client()->videoEdits()->list('w_1', mediaId: 'm_1', limit: 5);

        $this->assertSame(
            'https://api.fopost.com/v1/video-edits?workspace_id=w_1&media_id=m_1&limit=5',
            $this->transport->last()['url'],
        );
        $this->assertSame('ve_1', $jobs[0]->id);
    }

    public function testCreateSendsTheOperationsAndMapsTheJob(): void
    {
        $this->transport->push(201, ['status' => 'queued', 'progress' => 0, 'result_media' => null] + self::JOB);

        $job = $this->client()->videoEdits()->create('m_1', [
            ['op' => 'trim', 'params' => ['startSec' => 0, 'endSec' => 10]],
            ['op' => 'denoise'],
            ['op' => 'remove_silence', 'params' => []],
        ], workspaceId: 'w_1');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/video-edits', $this->transport->last()['url']);
        $this->assertSame([
            'workspace_id' => 'w_1',
            'media_id' => 'm_1',
            'operations' => [
                ['op' => 'trim', 'params' => ['startSec' => 0, 'endSec' => 10]],
                ['op' => 'denoise'],
                ['op' => 'remove_silence'],
            ],
        ], $this->transport->lastJson());
        $this->assertSame('queued', $job->status);
        $this->assertSame(0.0, $job->progress);
        $this->assertNull($job->resultMedia);
    }

    public function testCreateSurfacesARejectedOperation(): void
    {
        $this->transport->push(400, ['error' => 'validation_error', 'message' => 'Invalid video edit request']);

        $this->expectException(ValidationException::class);
        $this->client()->videoEdits()->create('m_1', [['op' => 'speed', 'params' => ['factor' => 9]]]);
    }

    public function testGetMapsTheFinishedJob(): void
    {
        $this->transport->push(200, self::JOB);

        $job = $this->client()->videoEdits()->get('ve_1', 'w_1');

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/video-edits/ve_1?workspace_id=w_1',
            $this->transport->last()['url'],
        );
        $this->assertSame('succeeded', $job->status);
        $this->assertSame(1.0, $job->progress);
        $this->assertSame('m_1', $job->sourceMediaId);
        $this->assertSame('trim', $job->operations[0]['op']);
        $this->assertSame('m_2', $job->resultMediaId);
        $this->assertSame('video/mp4', $job->resultMedia['mime_type'] ?? null);
        $this->assertSame('Subtitles', $job->extraOutputs[0]['label']);
        $this->assertSame('2026-11-20T10:01:00+00:00', $job->completedAt?->format('c'));
    }
}
