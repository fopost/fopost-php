<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Client;
use Fopost\Sdk\Http\HttpClient;
use InvalidArgumentException;

final class ClientTest extends TestCase
{
    public function testSendsTheApiKeyAndTheUserAgentOnEveryRequest(): void
    {
        $this->transport->push(200, ['data' => []]);
        $this->client()->workspaces()->list();

        $headers = $this->transport->last()['headers'];
        $this->assertSame('fop_test_key', $headers['X-API-Key']);
        $this->assertSame('fopost-php', $headers['User-Agent']);
        $this->assertSame('application/json', $headers['Accept']);
        $this->assertSame('application/json', $headers['Content-Type']);
    }

    public function testDefaultBaseUrlKeepsTheApiVersionSuffix(): void
    {
        $this->assertSame('https://api.fopost.com/api/v1', Client::DEFAULT_BASE_URL);

        $this->transport->push(200, ['data' => []]);
        $client = $this->client();
        $client->workspaces()->list();

        $this->assertSame('https://api.fopost.com/api/v1', $client->baseUrl());
        $this->assertSame('https://api.fopost.com/api/v1/workspaces', $this->transport->last()['url']);
    }

    public function testABareHostGetsTheApiVersionSuffixAppended(): void
    {
        $this->assertSame('https://api.fopost.com/api/v1', HttpClient::normalizeBaseUrl('https://api.fopost.com'));
        $this->assertSame('https://api.fopost.com/api/v1', HttpClient::normalizeBaseUrl('https://api.fopost.com/'));
        $this->assertSame(
            'https://api.fopost.com/api/v1',
            HttpClient::normalizeBaseUrl('https://api.fopost.com/api/v1'),
        );
        $this->assertSame('http://localhost:8080/api/v1', HttpClient::normalizeBaseUrl('http://localhost:8080'));
    }

    public function testAMissingApiKeyIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Client('', Client::DEFAULT_BASE_URL, 30.0, 3, $this->transport);
    }

    public function testExposesEveryResource(): void
    {
        $client = $this->client();
        $this->assertSame($client->posts(), $client->posts());
        $this->assertNotNull($client->accounts());
        $this->assertNotNull($client->workspaces());
        $this->assertNotNull($client->labels());
        $this->assertNotNull($client->ai());
    }

    public function testRequestReachesAnUnwrappedEndpoint(): void
    {
        $this->transport->push(200, ['ok' => true]);
        $body = $this->client()->request('GET', '/anything', null, ['a' => 1, 'b' => null]);

        $this->assertSame(['ok' => true], $body);
        $this->assertSame('https://api.fopost.com/api/v1/anything?a=1', $this->transport->last()['url']);
    }

    public function testA204ReturnsNothing(): void
    {
        $this->transport->push(204);
        $this->assertNull($this->client()->request('DELETE', '/posts/p_1'));
    }
}
