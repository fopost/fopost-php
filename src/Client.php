<?php

declare(strict_types=1);

namespace Fopost\Sdk;

use Fopost\Sdk\Http\HttpClient;
use Fopost\Sdk\Http\Transport;
use Fopost\Sdk\Resource\AccountGroupsResource;
use Fopost\Sdk\Resource\AccountsResource;
use Fopost\Sdk\Resource\AdsResource;
use Fopost\Sdk\Resource\AiResource;
use Fopost\Sdk\Resource\InboxResource;
use Fopost\Sdk\Resource\KnowledgeResource;
use Fopost\Sdk\Resource\LabelsResource;
use Fopost\Sdk\Resource\MediaResource;
use Fopost\Sdk\Resource\PostsResource;
use Fopost\Sdk\Resource\ValidateResource;
use Fopost\Sdk\Resource\WorkspacesResource;
use InvalidArgumentException;

/**
 * Client for the FoPost API.
 *
 *     $client = new \Fopost\Sdk\Client('fp_...');
 *     $accounts = $client->accounts()->list('9b2f6c1e-...');
 *
 * The key falls back to the FOPOST_API_KEY environment variable. Requests that
 * come back 429 are retried up to $maxRetries attempts, waiting for the
 * interval the API asks for in Retry-After.
 */
final class Client
{
    public const VERSION = '0.3.0';
    public const DEFAULT_BASE_URL = HttpClient::DEFAULT_BASE_URL;

    private readonly HttpClient $http;
    private readonly PostsResource $posts;
    private readonly AccountsResource $accounts;
    private readonly AccountGroupsResource $accountGroups;
    private readonly WorkspacesResource $workspaces;
    private readonly LabelsResource $labels;
    private readonly AiResource $ai;
    private readonly InboxResource $inbox;

    private readonly KnowledgeResource $knowledge;
    private readonly AdsResource $ads;
    private readonly MediaResource $media;
    private readonly ValidateResource $validate;

    public function __construct(
        ?string $apiKey = null,
        string $baseUrl = self::DEFAULT_BASE_URL,
        float $timeout = HttpClient::DEFAULT_TIMEOUT,
        int $maxRetries = HttpClient::DEFAULT_MAX_RETRIES,
        ?Transport $transport = null,
    ) {
        $key = $apiKey ?? (getenv('FOPOST_API_KEY') ?: null) ?? ($_ENV['FOPOST_API_KEY'] ?? null);
        if (!is_string($key) || $key === '') {
            throw new InvalidArgumentException(
                'fopost: an api key is required, pass it to the constructor or set FOPOST_API_KEY',
            );
        }

        $this->http = new HttpClient($key, $baseUrl, $timeout, $maxRetries, $transport);

        $this->posts = new PostsResource($this->http);
        $this->accounts = new AccountsResource($this->http);
        $this->accountGroups = new AccountGroupsResource($this->http);
        $this->workspaces = new WorkspacesResource($this->http);
        $this->labels = new LabelsResource($this->http);
        $this->ai = new AiResource($this->http);
        $this->inbox = new InboxResource($this->http);
        $this->knowledge = new KnowledgeResource($this->http);
        $this->ads = new AdsResource($this->http);
        $this->media = new MediaResource($this->http);
        $this->validate = new ValidateResource($this->http);
    }

    public function posts(): PostsResource
    {
        return $this->posts;
    }

    public function accounts(): AccountsResource
    {
        return $this->accounts;
    }

    public function accountGroups(): AccountGroupsResource
    {
        return $this->accountGroups;
    }

    public function workspaces(): WorkspacesResource
    {
        return $this->workspaces;
    }

    public function labels(): LabelsResource
    {
        return $this->labels;
    }

    public function ai(): AiResource
    {
        return $this->ai;
    }

    public function knowledge(): KnowledgeResource
    {
        return $this->knowledge;
    }

    public function inbox(): InboxResource
    {
        return $this->inbox;
    }

    public function ads(): AdsResource
    {
        return $this->ads;
    }

    public function media(): MediaResource
    {
        return $this->media;
    }

    public function validate(): ValidateResource
    {
        return $this->validate;
    }

    public function baseUrl(): string
    {
        return $this->http->baseUrl();
    }

    /**
     * Call an endpoint the SDK does not wrap yet. Returns the decoded body.
     *
     * @param array<string, mixed>|null $params
     */
    public function request(string $method, string $path, mixed $json = null, ?array $params = null): mixed
    {
        return $this->http->request($method, $path, $json, $params);
    }
}
