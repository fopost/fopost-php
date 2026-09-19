<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class ValidateTest extends TestCase
{
    public function testPostSendsTheDraftAndReadsEveryPlatform(): void
    {
        $this->transport->push(200, ['data' => [
            'ready' => false,
            'platforms' => [
                ['platform' => 'bluesky', 'ready' => true, 'issues' => [], 'score' => 82, 'signals' => [
                    ['level' => 'info', 'code' => 'no_hashtags', 'message' => 'No hashtags'],
                ]],
                ['platform' => 'twitter', 'ready' => false, 'issues' => ['Too long'], 'signals' => []],
            ],
        ]]);

        $result = $this->client()->validate()->post(
            ['bluesky', 'twitter'],
            'Hello',
            [['url' => 'https://yourbrand.com/a.png', 'mime_type' => 'image/png', 'size' => 1024]],
        );

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/validate/post', $this->transport->last()['url']);
        $this->assertSame(
            [
                'content' => 'Hello',
                'media' => [['url' => 'https://yourbrand.com/a.png', 'mime_type' => 'image/png', 'size' => 1024]],
                'platforms' => ['bluesky', 'twitter'],
            ],
            $this->transport->lastJson(),
        );
        $this->assertFalse($result->ready);
        $this->assertCount(2, $result->platforms);
        $this->assertTrue($result->platforms[0]->ready);
        $this->assertSame(82.0, $result->platforms[0]->score);
        $this->assertSame('no_hashtags', $result->platforms[0]->signals[0]->code);
        $this->assertSame(['Too long'], $result->platforms[1]->issues);
        $this->assertNull($result->platforms[1]->score);
    }

    public function testPostDefaultsContentAndMedia(): void
    {
        $this->transport->push(200, ['data' => ['ready' => true, 'platforms' => []]]);

        $this->client()->validate()->post(['linkedin']);

        $this->assertSame(
            ['content' => '', 'media' => [], 'platforms' => ['linkedin']],
            $this->transport->lastJson(),
        );
    }

    public function testLengthReadsTheCountPerPlatform(): void
    {
        $this->transport->push(200, ['data' => [
            'ok' => false,
            'platforms' => [
                ['platform' => 'twitter', 'length' => 300, 'limit' => 280, 'unit' => 'chars', 'ok' => false,
                    'signals' => [['level' => 'warn', 'code' => 'over_length', 'message' => 'Over by 20']]],
                ['platform' => 'linkedin', 'length' => 300, 'limit' => null, 'unit' => 'chars', 'ok' => true,
                    'signals' => []],
            ],
        ]]);

        $result = $this->client()->validate()->length('some text', ['twitter', 'linkedin']);

        $this->assertSame('https://api.fopost.com/v1/validate/length', $this->transport->last()['url']);
        $this->assertSame(
            ['text' => 'some text', 'platforms' => ['twitter', 'linkedin']],
            $this->transport->lastJson(),
        );
        $this->assertFalse($result->ok);
        $this->assertSame(280, $result->platforms[0]->limit);
        $this->assertSame('over_length', $result->platforms[0]->signals[0]->code);
        $this->assertNull($result->platforms[1]->limit);
        $this->assertTrue($result->platforms[1]->ok);
    }

    public function testMediaSendsTheUrlAndReadsTheVerdict(): void
    {
        $this->transport->push(200, ['data' => [
            'ok' => true,
            'issues' => [],
            'name' => 'a.png',
            'size' => 2048,
            'mime_type' => 'image/png',
            'type' => 'image',
        ]]);

        $result = $this->client()->validate()->media('https://yourbrand.com/a.png');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/validate/media', $this->transport->last()['url']);
        $this->assertSame(['url' => 'https://yourbrand.com/a.png'], $this->transport->lastJson());
        $this->assertTrue($result->ok);
        $this->assertSame('image/png', $result->mimeType);
        $this->assertSame('image', $result->type);
        $this->assertSame(2048, $result->size);
    }

    public function testMediaReadsAFailedCheckWithoutThrowing(): void
    {
        $this->transport->push(200, ['data' => [
            'ok' => false,
            'issues' => ['File is too large'],
            'name' => 'big.mov',
            'size' => 99999999,
        ]]);

        $result = $this->client()->validate()->media('https://yourbrand.com/big.mov');

        $this->assertFalse($result->ok);
        $this->assertSame(['File is too large'], $result->issues);
        $this->assertNull($result->mimeType);
        $this->assertNull($result->type);
    }
}
