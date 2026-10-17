<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\ApiException;

final class PostFilesTest extends TestCase
{
    private const LIMITS = ['data' => ['maxFileBytes' => 5368709120, 'multipartMaxFileBytes' => 8]];

    private const PRESIGNED = [
        'uploadId' => 'up_1',
        'uploadUrl' => 'https://storage.example.com/staging/up_1',
        'method' => 'PUT',
        'headers' => ['Content-Type' => 'video/mp4'],
    ];

    private const ASSET = [
        'id' => 'm_1',
        'type' => 'video',
        'name' => 'clip.mp4',
        'url' => 'https://storage.example.com/m_1',
        'size' => 12,
    ];

    public function testSmallFilesGoOutAsOneMultipartRequest(): void
    {
        $this->transport->push(200, self::LIMITS);
        $this->transport->push(201, ['data' => ['id' => 'p_1', 'status' => 'draft']]);

        $post = $this->client()->posts()->create(
            workspaceId: 'w_1',
            content: 'Hello',
            accounts: ['a_1'],
            files: [['filename' => 'logo.png', 'contents' => 'PNG!', 'mimeType' => 'image/png']],
        );

        $this->assertSame(2, $this->transport->requestCount());
        [$limits, $create] = $this->transport->requests;
        $this->assertSame('https://api.fopost.com/v1/media/limits?workspaceId=w_1', $limits['url']);

        $this->assertSame('POST', $create['method']);
        $this->assertSame('https://api.fopost.com/v1/posts', $create['url']);
        $this->assertStringStartsWith('multipart/form-data; boundary=', $create['headers']['Content-Type']);
        $this->assertSame('fop_test_key', $create['headers']['X-API-Key']);

        $body = (string) $create['body'];
        $this->assertStringContainsString('Content-Disposition: form-data; name="post"', $body);
        $this->assertStringContainsString(
            'Content-Disposition: form-data; name="files"; filename="logo.png"',
            $body,
        );
        $this->assertStringContainsString("Content-Type: image/png\r\n\r\nPNG!\r\n", $body);

        preg_match('/name="post"\r\n\r\n(.*?)\r\n--/s', $body, $m);
        $json = json_decode($m[1], true);
        $this->assertSame('w_1', $json['workspace_id']);
        $this->assertSame([['text' => 'Hello']], $json['content']);
        $this->assertSame(['a_1'], $json['accounts']);
        $this->assertSame('p_1', $post->id);
    }

    public function testALargeFileIsUploadedDirectAndAttachedToTheFirstBlock(): void
    {
        $this->transport->push(200, self::LIMITS);
        $this->transport->push(201, ['data' => self::PRESIGNED]);
        $this->transport->push(200, '');
        $this->transport->push(201, ['data' => self::ASSET]);
        $this->transport->push(201, ['data' => ['id' => 'p_1', 'status' => 'draft']]);

        $this->client()->posts()->create(
            workspaceId: 'w_1',
            content: 'Watch this',
            accounts: ['a_1'],
            files: [['filename' => 'clip.mp4', 'contents' => 'twelve bytes', 'mimeType' => 'video/mp4']],
        );

        $this->assertSame(5, $this->transport->requestCount());
        [, $presign, $put, $complete, $create] = $this->transport->requests;

        $this->assertSame('https://api.fopost.com/v1/media/presign', $presign['url']);
        $this->assertSame(12, json_decode((string) $presign['body'], true)['size']);
        $this->assertSame(self::PRESIGNED['uploadUrl'], $put['url']);
        $this->assertSame('twelve bytes', $put['body']);
        $this->assertSame('https://api.fopost.com/v1/media/presign/up_1/complete', $complete['url']);

        $this->assertSame('application/json', $create['headers']['Content-Type']);
        $json = json_decode((string) $create['body'], true);
        $this->assertSame([[
            'text' => 'Watch this',
            'media' => [[
                'type' => 'video',
                'name' => 'clip.mp4',
                'url' => 'https://storage.example.com/m_1',
                'size' => 12,
            ]],
        ]], $json['content']);
    }

    public function testMultipartFallsBackToTheDefaultCeilingWhenLimitsFails(): void
    {
        $this->transport->push(500, ['error' => 'internal_error']);
        $this->transport->push(201, ['data' => ['id' => 'p_1', 'status' => 'draft']]);

        $this->client()->posts()->create(
            workspaceId: 'w_1',
            content: 'Hello',
            files: [['filename' => 'logo.png', 'contents' => 'PNG!', 'mimeType' => 'image/png']],
        );

        $this->assertSame(2, $this->transport->requestCount());
        $this->assertStringStartsWith('multipart/form-data', $this->transport->last()['headers']['Content-Type']);
    }

    public function testUpdateWithoutContentAppendsToTheExistingFirstBlock(): void
    {
        $this->transport->push(200, self::LIMITS);
        $this->transport->push(200, ['data' => [
            'id' => 'p_1',
            'status' => 'draft',
            'workspace_id' => 'w_9',
            'content' => [['text' => 'Keep me', 'media' => [['type' => 'image', 'url' => 'https://x/old.png']]]],
        ]]);
        $this->transport->push(201, ['data' => self::PRESIGNED]);
        $this->transport->push(200, '');
        $this->transport->push(201, ['data' => self::ASSET]);
        $this->transport->push(200, ['data' => ['id' => 'p_1', 'status' => 'draft']]);

        $this->client()->posts()->update(
            'p_1',
            files: [['filename' => 'clip.mp4', 'contents' => 'twelve bytes', 'mimeType' => 'video/mp4']],
        );

        [$limits, $get, $presign, , , $put] = $this->transport->requests;
        $this->assertSame('https://api.fopost.com/v1/media/limits', $limits['url']);
        $this->assertSame('https://api.fopost.com/v1/posts/p_1', $get['url']);
        $this->assertSame('w_9', json_decode((string) $presign['body'], true)['workspaceId']);

        $this->assertSame('PUT', $put['method']);
        $this->assertSame('https://api.fopost.com/v1/posts/p_1', $put['url']);
        $media = json_decode((string) $put['body'], true)['content'][0]['media'];
        $this->assertSame('Keep me', json_decode((string) $put['body'], true)['content'][0]['text']);
        $this->assertSame('https://x/old.png', $media[0]['url']);
        $this->assertSame('https://storage.example.com/m_1', $media[1]['url']);
    }

    public function testAnOversizeMultipartPostSurfacesTheLimit(): void
    {
        $this->transport->push(200, self::LIMITS);
        $this->transport->push(413, [
            'error' => 'file_too_large',
            'message' => 'This file is larger than your plan allows.',
            'max_file_bytes' => 104857600,
            'file_bytes' => 4,
            'upgrade_url' => 'https://app.fopost.com/settings/billing',
        ]);

        try {
            $this->client()->posts()->create(
                workspaceId: 'w_1',
                content: 'Hello',
                files: [['filename' => 'logo.png', 'contents' => 'PNG!', 'mimeType' => 'image/png']],
            );
            $this->fail('expected the 413 to throw');
        } catch (ApiException $e) {
            $this->assertSame(413, $e->getStatus());
            $this->assertSame('file_too_large', $e->getErrorCode());
            $this->assertSame(104857600, $e->getBody()['max_file_bytes']);
        }
    }
}
