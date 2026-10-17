<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\ApiException;
use Fopost\Sdk\Exception\PermissionDeniedException;

final class MediaTest extends TestCase
{
    private const PRESIGNED = [
        'uploadId' => 'up_1',
        'uploadUrl' => 'https://storage.example.com/staging/up_1?sig=abc',
        'method' => 'PUT',
        'headers' => ['Content-Type' => 'image/png'],
        'expiresAt' => '2026-09-19T12:00:00Z',
        'maxFileBytes' => 5368709120,
    ];

    private const LIMITS = [
        'maxFileBytes' => 419430400,
        'source' => 'accounts',
        'connectedAccounts' => 3,
        'largeUploadsEnabled' => false,
        'nextTier' => ['upToAccounts' => 15, 'maxFileBytes' => 2147483648],
        'multipartMaxFileBytes' => 104857600,
        'maxFilesPerUpload' => 5,
        'directUploadAvailable' => true,
        'allowedTypes' => ['image/png', 'video/mp4'],
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
        $this->assertSame(5368709120, $upload->maxFileBytes);
    }

    public function testPresignSendsTheOptionalBrandFields(): void
    {
        $this->transport->push(201, ['data' => self::PRESIGNED]);

        $this->client()->media()->presign(
            'w_1',
            'face.png',
            'image/png',
            4,
            folderId: 'f_1',
            origin: 'chat',
            brandRole: 'person',
            brandVoiceId: 'bv_1',
            personConsent: ['subject' => 'Jo', 'statement' => 'Jo agreed'],
        );

        $this->assertSame([
            'workspaceId' => 'w_1',
            'filename' => 'face.png',
            'mimeType' => 'image/png',
            'size' => 4,
            'folderId' => 'f_1',
            'origin' => 'chat',
            'brandRole' => 'person',
            'brandVoiceId' => 'bv_1',
            'personConsent' => ['subject' => 'Jo', 'statement' => 'Jo agreed'],
        ], $this->transport->lastJson());
    }

    public function testLimitsReadsTheCeilingsForTheWorkspace(): void
    {
        $this->transport->push(200, ['data' => self::LIMITS]);

        $limits = $this->client()->media()->limits('w_1');

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/media/limits?workspaceId=w_1', $this->transport->last()['url']);
        $this->assertSame(419430400, $limits->maxFileBytes);
        $this->assertSame('accounts', $limits->source);
        $this->assertSame(3, $limits->connectedAccounts);
        $this->assertFalse($limits->largeUploadsEnabled);
        $this->assertSame(15, $limits->nextTier?->upToAccounts);
        $this->assertSame(2147483648, $limits->nextTier?->maxFileBytes);
        $this->assertSame(104857600, $limits->multipartMaxFileBytes);
        $this->assertSame(5, $limits->maxFilesPerUpload);
        $this->assertTrue($limits->directUploadAvailable);
        $this->assertSame(['image/png', 'video/mp4'], $limits->allowedTypes);
    }

    public function testLimitsWithoutAWorkspaceSendsNoQuery(): void
    {
        $this->transport->push(200, ['data' => ['nextTier' => null] + self::LIMITS]);

        $limits = $this->client()->media()->limits();

        $this->assertSame('https://api.fopost.com/v1/media/limits', $this->transport->last()['url']);
        $this->assertNull($limits->nextTier);
    }

    public function testAnOversizePresignSurfacesTheLimit(): void
    {
        $this->transport->push(413, [
            'error' => 'file_too_large',
            'message' => 'This file is larger than your plan allows.',
            'max_file_bytes' => 104857600,
            'file_bytes' => 209715200,
            'upgrade_url' => 'https://app.fopost.com/settings/billing',
        ]);

        try {
            $this->client()->media()->presign('w_1', 'big.mp4', 'video/mp4', 209715200);
            $this->fail('expected the 413 to throw');
        } catch (ApiException $e) {
            $this->assertSame(413, $e->getStatus());
            $this->assertSame('file_too_large', $e->getErrorCode());
            $this->assertSame(104857600, $e->getBody()['max_file_bytes']);
            $this->assertSame(209715200, $e->getBody()['file_bytes']);
            $this->assertSame('https://app.fopost.com/settings/billing', $e->getBody()['upgrade_url']);
        }
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
