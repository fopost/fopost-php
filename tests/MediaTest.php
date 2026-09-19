<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\PermissionDeniedException;

final class MediaTest extends TestCase
{
    private const PRESIGNED = [
        'uploadId' => 'up_1',
        'uploadUrl' => 'https://storage.example.com/staging/up_1?sig=abc',
        'method' => 'PUT',
        'headers' => ['Content-Type' => 'image/png'],
        'expiresAt' => '2026-09-19T12:00:00Z',
    ];

    private const ASSET = [
        'id' => 'm_1',
        'type' => 'image',
        'name' => 'logo.png',
        'url' => 'https://storage.example.com/m_1',
        'previewUrl' => 'https://api.fopost.com/v1/media/m_1/file',
        'size' => 4,
    ];

    public function testPresignPostsTheDeclaredFile(): void
    {
        $this->transport->push(201, ['data' => self::PRESIGNED]);

        $upload = $this->client()->media()->presign('w_1', 'logo.png', 'image/png', 4);

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/media/presign', $this->transport->last()['url']);
        $this->assertSame(
            ['workspaceId' => 'w_1', 'filename' => 'logo.png', 'mimeType' => 'image/png', 'size' => 4],
            $this->transport->lastJson(),
        );
        $this->assertSame('up_1', $upload->uploadId);
        $this->assertSame(['Content-Type' => 'image/png'], $upload->headers);
        $this->assertSame('2026-09-19T12:00:00+00:00', $upload->expiresAt?->format('c'));
    }

    public function testCompleteReturnsTheAsset(): void
    {
        $this->transport->push(201, ['data' => self::ASSET]);

        $asset = $this->client()->media()->complete('up_1');

        $this->assertSame('https://api.fopost.com/v1/media/presign/up_1/complete', $this->transport->last()['url']);
        $this->assertNull($this->transport->last()['body']);
        $this->assertSame('m_1', $asset->id);
        $this->assertSame('image', $asset->type);
        $this->assertSame('https://api.fopost.com/v1/media/m_1/file', $asset->previewUrl);
    }

    public function testUploadDirectPresignsPutsAndCompletes(): void
    {
        $this->transport->push(201, ['data' => self::PRESIGNED]);
        $this->transport->push(200, '');
        $this->transport->push(201, ['data' => self::ASSET]);

        $asset = $this->client()->media()->uploadDirect('w_1', 'logo.png', 'image/png', 'PNG!');

        $this->assertSame(3, $this->transport->requestCount());
        [$presign, $put, $complete] = $this->transport->requests;

        $this->assertSame('https://api.fopost.com/v1/media/presign', $presign['url']);
        $this->assertSame(4, json_decode((string) $presign['body'], true)['size']);

        $this->assertSame('PUT', $put['method']);
        $this->assertSame(self::PRESIGNED['uploadUrl'], $put['url']);
        $this->assertSame('PNG!', $put['body']);
        $this->assertSame(['Content-Type' => 'image/png', 'Content-Length' => '4'], $put['headers']);
        $this->assertArrayNotHasKey('X-API-Key', $put['headers']);

        $this->assertSame('https://api.fopost.com/v1/media/presign/up_1/complete', $complete['url']);
        $this->assertSame('m_1', $asset->id);
    }

    public function testUploadDirectStopsWhenThePutIsRejected(): void
    {
        $this->transport->push(201, ['data' => self::PRESIGNED]);
        $this->transport->push(403, '<Error>SignatureDoesNotMatch</Error>');

        try {
            $this->client()->media()->uploadDirect('w_1', 'logo.png', 'image/png', 'PNG!');
            $this->fail('expected the rejected PUT to throw');
        } catch (PermissionDeniedException $e) {
            $this->assertSame(403, $e->getStatus());
        }

        $this->assertSame(2, $this->transport->requestCount());
    }
}
