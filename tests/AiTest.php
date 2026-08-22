<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class AiTest extends TestCase
{
    public function testCreditsReadsTheBalance(): void
    {
        $this->transport->push(200, ['data' => [
            'credits_remaining' => 120,
            'credits_used' => 80,
            'credits_total' => 200,
            'period_end' => '2026-02-01T00:00:00Z',
        ]]);

        $balance = $this->client()->ai()->credits();

        $this->assertSame('https://api.fopost.com/api/v1/ai/credits', $this->transport->last()['url']);
        $this->assertSame(120, $balance->creditsRemaining);
        $this->assertSame(200, $balance->creditsTotal);
        $this->assertSame('2026-02-01', $balance->periodEnd?->format('Y-m-d'));
    }

    public function testGenerateCaptionDropsUnsetFields(): void
    {
        $this->transport->push(200, ['caption' => 'Ship it', 'credits' => ['charged' => 2, 'remaining' => 118]]);

        $result = $this->client()->ai()->generateCaption(
            currentCaption: 'draft',
            platforms: ['bluesky'],
            charLimit: 280,
        );

        $this->assertSame('https://api.fopost.com/api/v1/ai/generate-caption', $this->transport->last()['url']);
        $this->assertSame(
            ['current_caption' => 'draft', 'platforms' => ['bluesky'], 'char_limit' => 280],
            $this->transport->lastJson(),
        );
        $this->assertSame('Ship it', $result->caption);
        $this->assertSame(2, $result->credits?->charged);
    }

    public function testRewriteReturnsOneVariantPerPlatform(): void
    {
        $this->transport->push(200, ['data' => [
            'results' => [
                ['platform' => 'linkedin', 'content' => 'Long form'],
                ['platform' => 'bluesky', 'content' => 'Short form'],
            ],
            'credits' => ['charged' => 2, 'remaining' => 116],
        ]]);

        $result = $this->client()->ai()->rewrite('Hello', ['linkedin', 'bluesky'], tone: 'friendly');

        $this->assertSame(
            ['content' => 'Hello', 'platforms' => ['linkedin', 'bluesky'], 'tone' => 'friendly'],
            $this->transport->lastJson(),
        );
        $this->assertCount(2, $result->results);
        $this->assertSame('linkedin', $result->results[0]->platform);
    }

    public function testRepurposeUrlFansOutAnArticle(): void
    {
        $this->transport->push(200, ['data' => [
            'url' => 'https://example.com/post',
            'title' => 'A post',
            'posts' => ['bluesky' => 'Short', 'linkedin' => 'Long'],
        ]]);

        $result = $this->client()->ai()->repurposeUrl('https://example.com/post', ['bluesky', 'linkedin']);

        $this->assertSame('https://api.fopost.com/api/v1/ai/repurpose-url', $this->transport->last()['url']);
        $this->assertSame('A post', $result->title);
        $this->assertSame('Short', $result->posts['bluesky']);
    }
}
