<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\RateLimitException;
use Fopost\Sdk\Http\HttpClient;
use Fopost\Sdk\Http\Response;

final class RetryTest extends TestCase
{
    /** @var array<int, float> */
    private array $slept = [];

    private function http(int $maxRetries = 3): HttpClient
    {
        $this->slept = [];

        return new HttpClient(
            'fop_test_key',
            HttpClient::DEFAULT_BASE_URL,
            30.0,
            $maxRetries,
            $this->transport,
            function (float $seconds): void {
                $this->slept[] = $seconds;
            },
        );
    }

    public function testA429IsRetriedAndRetryAfterIsHonoured(): void
    {
        $this->transport->push(429, ['error' => 'rate_limited'], ['retry-after' => '2.5']);
        $this->transport->push(200, ['data' => ['ok' => true]]);

        $body = $this->http()->get('/workspaces');

        $this->assertSame(['data' => ['ok' => true]], $body);
        $this->assertSame(2, $this->transport->requestCount());
        $this->assertSame([2.5], $this->slept);
    }

    public function testARetryAfterHttpDateIsHonoured(): void
    {
        $when = gmdate('D, d M Y H:i:s \G\M\T', time() + 30);
        $this->transport->push(429, ['error' => 'rate_limited'], ['retry-after' => $when]);
        $this->transport->push(200, ['data' => []]);

        $this->http()->get('/workspaces');

        $this->assertCount(1, $this->slept);
        $this->assertGreaterThanOrEqual(28.0, $this->slept[0]);
        $this->assertLessThanOrEqual(30.0, $this->slept[0]);
    }

    public function testTheWaitIsCappedAtOneMinute(): void
    {
        $this->transport->push(429, ['error' => 'rate_limited'], ['retry-after' => '9000']);
        $this->transport->push(200, ['data' => []]);

        $this->http()->get('/workspaces');

        $this->assertSame([HttpClient::MAX_RETRY_WAIT], $this->slept);
    }

    public function testWithoutARetryAfterHeaderItWaitsOneSecond(): void
    {
        $this->transport->push(429, ['error' => 'rate_limited']);
        $this->transport->push(200, ['data' => []]);

        $this->http()->get('/workspaces');

        $this->assertSame([1.0], $this->slept);
    }

    public function testTheLastAttemptRaisesRateLimitExceptionCarryingRetryAfter(): void
    {
        $this->transport->push(429, ['error' => 'rate_limited'], ['retry-after' => '5']);
        $this->transport->push(429, ['error' => 'rate_limited', 'message' => 'Too many'], ['retry-after' => '7']);

        try {
            $this->http(2)->get('/workspaces');
            $this->fail('expected a RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatus());
            $this->assertSame('Too many', $e->getMessage());
            $this->assertSame('rate_limited', $e->getErrorCode());
            $this->assertSame(7.0, $e->getRetryAfter());
        }

        $this->assertSame(2, $this->transport->requestCount());
        $this->assertSame([5.0], $this->slept);
    }

    public function testMaxRetriesOfOneNeverRetries(): void
    {
        $this->transport->push(429, ['error' => 'rate_limited'], ['retry-after' => '5']);

        $this->expectException(RateLimitException::class);
        try {
            $this->http(1)->get('/workspaces');
        } finally {
            $this->assertSame(1, $this->transport->requestCount());
            $this->assertSame([], $this->slept);
        }
    }

    public function testRetryAfterParsingHandlesJunk(): void
    {
        $this->assertNull(HttpClient::retryAfterSeconds(new Response(429, [])));
        $this->assertNull(HttpClient::retryAfterSeconds(new Response(429, ['retry-after' => 'not a date'])));
        $this->assertSame(0.0, HttpClient::retryAfterSeconds(new Response(429, ['retry-after' => '-4'])));
    }
}
