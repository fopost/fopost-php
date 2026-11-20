<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\ApiException;

final class FontsTest extends TestCase
{
    private const UPLOADED = [
        'id' => 'f_1',
        'family' => 'Brand Sans',
        'style' => 'italic',
        'weight' => 700,
        'format' => 'woff2',
        'file_url' => 'https://api.fopost.com/v1/fonts/f_1/file',
        'created_at' => '2026-11-20T10:00:00Z',
    ];

    public function testListReadsTheLibraryAndTheWorkspaceFonts(): void
    {
        $this->transport->push(200, [
            'library' => [[
                'slug' => 'inter',
                'family' => 'Inter',
                'category' => 'sans-serif',
                'scripts' => ['latin', 'cyrillic'],
                'weights' => [400, 700],
                'files' => [['weight' => 400, 'url' => '/fonts/inter-400.woff2']],
            ]],
            'uploaded' => [self::UPLOADED],
        ]);

        $catalog = $this->client()->fonts()->list('w_1');

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/fonts?workspace_id=w_1', $this->transport->last()['url']);
        $this->assertSame('inter', $catalog->library[0]->slug);
        $this->assertSame(['latin', 'cyrillic'], $catalog->library[0]->scripts);
        $this->assertSame([400, 700], $catalog->library[0]->weights);
        $this->assertCount(1, $catalog->library[0]->files);
        $this->assertSame('f_1', $catalog->uploaded[0]->id);
        $this->assertSame('italic', $catalog->uploaded[0]->style);
        $this->assertSame(700, $catalog->uploaded[0]->weight);
        $this->assertSame('https://api.fopost.com/v1/fonts/f_1/file', $catalog->uploaded[0]->fileUrl);
    }

    public function testListWithoutAWorkspaceSendsNoQuery(): void
    {
        $this->transport->push(200, ['library' => [], 'uploaded' => []]);

        $catalog = $this->client()->fonts()->list();

        $this->assertSame('https://api.fopost.com/v1/fonts', $this->transport->last()['url']);
        $this->assertSame([], $catalog->uploaded);
    }

    public function testUploadSendsOneMultipartFile(): void
    {
        $this->transport->push(201, self::UPLOADED);

        $font = $this->client()->fonts()->upload(
            'w_1',
            ['filename' => 'brand.woff2', 'contents' => 'wOF2', 'mimeType' => 'font/woff2'],
            family: 'Brand Sans',
            weight: 700,
            style: 'italic',
        );

        $request = $this->transport->last();
        $body = (string) $request['body'];
        $this->assertSame('POST', $request['method']);
        $this->assertSame('https://api.fopost.com/v1/fonts', $request['url']);
        $this->assertStringStartsWith('multipart/form-data; boundary=', $request['headers']['Content-Type']);
        $this->assertStringContainsString('name="workspace_id"' . "\r\n\r\nw_1", $body);
        $this->assertStringContainsString('name="family"' . "\r\n\r\nBrand Sans", $body);
        $this->assertStringContainsString('name="weight"' . "\r\n\r\n700", $body);
        $this->assertStringContainsString('name="style"' . "\r\n\r\nitalic", $body);
        $this->assertStringContainsString('name="file"; filename="brand.woff2"', $body);
        $this->assertStringContainsString("Content-Type: font/woff2\r\n\r\nwOF2", $body);
        $this->assertSame('f_1', $font->id);
        $this->assertSame('woff2', $font->format);
    }

    public function testUploadReadsAPath(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'font');
        $this->assertIsString($path);
        file_put_contents($path, 'OTTO');
        $this->transport->push(201, self::UPLOADED);

        try {
            $this->client()->fonts()->upload('w_1', $path);
        } finally {
            unlink($path);
        }

        $body = (string) $this->transport->last()['body'];
        $this->assertStringContainsString('filename="' . basename($path) . '"', $body);
        $this->assertStringContainsString("\r\n\r\nOTTO\r\n", $body);
        $this->assertStringNotContainsString('name="family"', $body);
    }

    public function testAnOversizeFontSurfacesTheError(): void
    {
        $this->transport->push(413, ['error' => 'file_too_large', 'message' => 'Font files can be up to 5 MB']);

        try {
            $this->client()->fonts()->upload('w_1', ['filename' => 'big.ttf', 'contents' => 'x']);
            $this->fail('expected the 413 to throw');
        } catch (ApiException $e) {
            $this->assertSame(413, $e->getStatus());
            $this->assertSame('file_too_large', $e->getErrorCode());
        }
    }

    public function testDeleteSendsADelete(): void
    {
        $this->transport->push(204);

        $this->client()->fonts()->delete('f_1');

        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/fonts/f_1', $this->transport->last()['url']);
    }
}
