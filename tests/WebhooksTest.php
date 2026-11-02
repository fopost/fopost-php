<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

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
            'event' => 'post.published',
            'data' => ['id' => 'post_1', 'status' => 'published'],
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
            'X-FoPost-Event' => 'post.published',
        ];
    }

    public function testAValidTimestampedSignaturePassesAndParsesTheEnvelope(): void
    {
        $body = $this->body();

        $event = Webhooks::verify($body, $this->timestampedHeaders($body), self::SECRET, self::NOW + 10);

        $this->assertSame('post.published', $event->event);
        $this->assertSame('2026-10-10T09:00:01+00:00', $event->timestamp?->format(DATE_ATOM));
        $this->assertSame('published', $event->data['status']);
    }

    public function testHeaderNamesAreMatchedWithoutCaseAndAsLists(): void
    {
        $body = $this->body();
        $headers = [];
        foreach ($this->timestampedHeaders($body) as $name => $value) {
            $headers[strtolower($name)] = [$value];
        }

        $event = Webhooks::verify($body, $headers, self::SECRET, self::NOW);

        $this->assertSame('post_1', $event->data['id']);
    }

    public function testATamperedBodyFails(): void
    {
        $body = $this->body();
        $headers = $this->timestampedHeaders($body);
        $tampered = str_replace('post_1', 'post_2', $body);

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
        $this->assertSame('post.published', Webhooks::verify($body, $edge, self::SECRET, self::NOW)->event);
    }

    public function testTheLegacyBodySignaturePassesOnlyWithoutATimestamp(): void
    {
        $body = $this->body();

        $event = Webhooks::verify($body, ['X-FoPost-Signature' => Webhooks::sign($body, self::SECRET)], self::SECRET);
        $this->assertSame('post.published', $event->event);

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
