<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Blog;
use Fopost\Sdk\Exception\WebhookSignatureException;
use Fopost\Sdk\Webhooks;
use PHPUnit\Framework\TestCase;

final class WebhooksTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';
    private const NOW = 1_760_000_000;

    private function body(): string
    {
        return json_encode([
            'event' => 'article.published',
            'data' => [
                'id' => 'art_1',
                'title' => 'How to sharpen shears',
                'slug' => 'how-to-sharpen-shears',
                'excerpt' => null,
                'metaTitle' => null,
                'metaDescription' => null,
                'bodyMarkdown' => '# Shears',
                'bodyHtml' => '<h1>Shears</h1>',
                'featuredImage' => ['mediaId' => 'med_1', 'url' => 'https://cdn.example/1.jpg', 'alt' => 'Shears'],
                'images' => [
                    ['mediaId' => 'med_2', 'url' => 'https://cdn.example/2.jpg', 'alt' => 'Stone', 'position' => 1],
                ],
                'tags' => ['tools'],
                'keyword' => 'sharpen garden shears',
                'wordCount' => 1200,
                'publishedAt' => '2026-10-10T09:00:00.000Z',
                'publishedUrl' => 'https://yourbrand.com/blog/how-to-sharpen-shears',
            ],
            'timestamp' => '2026-10-10T09:00:01.000Z',
        ], JSON_THROW_ON_ERROR);
    }

    /** @return array<string, string> */
    private function timestampedHeaders(string $body, int $timestamp = self::NOW): array
    {
        return [
            'X-FoPost-Signature' => Webhooks::sign($body, self::SECRET),
            'X-FoPost-Timestamp' => (string) $timestamp,
            'X-FoPost-Signature-256' => Webhooks::signTimestamped($body, self::SECRET, $timestamp),
            'X-FoPost-Event' => 'article.published',
        ];
    }

    public function testAValidTimestampedSignaturePassesAndParsesTheArticle(): void
    {
        $body = $this->body();

        $event = Webhooks::verify($body, $this->timestampedHeaders($body), self::SECRET, self::NOW + 10);

        $this->assertSame(Blog::ARTICLE_PUBLISHED_EVENT, $event->event);
        $this->assertSame('2026-10-10T09:00:01+00:00', $event->timestamp?->format(DATE_ATOM));
        $article = $event->article();
        $this->assertSame('how-to-sharpen-shears', $article->slug);
        $this->assertSame('https://yourbrand.com/blog/how-to-sharpen-shears', $article->publishedUrl);
        $this->assertSame('med_1', $article->featuredImage?->mediaId);
        $this->assertSame(1, $article->images[0]->position);
        $this->assertSame(1200, $article->wordCount);
    }

    public function testHeaderNamesAreMatchedWithoutCaseAndAsLists(): void
    {
        $body = $this->body();
        $headers = [];
        foreach ($this->timestampedHeaders($body) as $name => $value) {
            $headers[strtolower($name)] = [$value];
        }

        $event = Webhooks::verify($body, $headers, self::SECRET, self::NOW);

        $this->assertSame('art_1', $event->data['id']);
    }

    public function testATamperedBodyFails(): void
    {
        $body = $this->body();
        $headers = $this->timestampedHeaders($body);
        $tampered = str_replace('how-to-sharpen-shears', 'how-to-drop-tables', $body);

        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessage('does not match');
        Webhooks::verify($tampered, $headers, self::SECRET, self::NOW);
    }

    public function testAStaleTimestampFailsEvenWithAValidSignature(): void
    {
        $body = $this->body();
        $headers = $this->timestampedHeaders($body, self::NOW - 301);

        try {
            Webhooks::verify($body, $headers, self::SECRET, self::NOW);
            $this->fail('expected a stale timestamp to be refused');
        } catch (WebhookSignatureException $e) {
            $this->assertSame('stale_timestamp', $e->getErrorCode());
        }

        // Exactly at the edge is still inside the tolerance.
        $edge = $this->timestampedHeaders($body, self::NOW - 300);
        $this->assertSame('article.published', Webhooks::verify($body, $edge, self::SECRET, self::NOW)->event);
    }

    public function testTheLegacyBodySignaturePassesOnlyWithoutATimestamp(): void
    {
        $body = $this->body();

        $event = Webhooks::verify($body, ['X-FoPost-Signature' => Webhooks::sign($body, self::SECRET)], self::SECRET);
        $this->assertSame('article.published', $event->event);

        // A timestamp with no timestamped signature must not fall back to the plain one.
        $this->expectException(WebhookSignatureException::class);
        Webhooks::verify($body, [
            'X-FoPost-Signature' => Webhooks::sign($body, self::SECRET),
            'X-FoPost-Timestamp' => (string) self::NOW,
        ], self::SECRET, self::NOW);
    }

    public function testAWrongSecretAndAMissingSignatureFail(): void
    {
        $body = $this->body();

        try {
            Webhooks::verify($body, $this->timestampedHeaders($body), 'whsec_other', self::NOW);
            $this->fail('expected the wrong secret to be refused');
        } catch (WebhookSignatureException $e) {
            $this->assertSame('invalid_signature', $e->getErrorCode());
        }

        try {
            Webhooks::verify($body, [], self::SECRET, self::NOW);
            $this->fail('expected a missing signature to be refused');
        } catch (WebhookSignatureException $e) {
            $this->assertSame('missing_signature', $e->getErrorCode());
        }
    }
}
