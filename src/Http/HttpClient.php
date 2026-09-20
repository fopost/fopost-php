<?php

declare(strict_types=1);

namespace Fopost\Sdk\Http;

use Fopost\Sdk\Exception\ApiException;
use Fopost\Sdk\Exception\ErrorFactory;
use Fopost\Sdk\Exception\FopostException;
use Fopost\Sdk\Exception\RateLimitException;
use InvalidArgumentException;

/** Auth headers, JSON coding, envelope unwrap, and the retry loop. */
final class HttpClient
{
    public const DEFAULT_BASE_URL = 'https://api.fopost.com/v1';
    public const API_PATH_SUFFIX = '/v1';
    public const DEFAULT_TIMEOUT = 30.0;
    public const DEFAULT_MAX_RETRIES = 3;
    public const MAX_RETRY_WAIT = 60.0;
    public const USER_AGENT = 'fopost-php';

    private readonly string $baseUrl;
    private readonly Transport $transport;

    /** @var callable(float): void */
    private $sleeper;

    public function __construct(
        private readonly string $apiKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
        float $timeout = self::DEFAULT_TIMEOUT,
        private readonly int $maxRetries = self::DEFAULT_MAX_RETRIES,
        ?Transport $transport = null,
        ?callable $sleeper = null,
    ) {
        if ($apiKey === '') {
            throw new InvalidArgumentException('fopost: api_key is required');
        }
        if ($maxRetries < 1) {
            throw new InvalidArgumentException('fopost: max_retries must be at least 1');
        }

        $this->baseUrl = self::normalizeBaseUrl($baseUrl);
        $this->transport = $transport ?? new CurlTransport($timeout);
        $this->sleeper = $sleeper ?? static function (float $seconds): void {
            usleep((int) round($seconds * 1_000_000));
        };
    }

    /**
     * A host with no path gets the API path suffix appended, so both
     * https://api.fopost.com and https://api.fopost.com/v1 work.
     */
    public static function normalizeBaseUrl(string $baseUrl): string
    {
        $trimmed = rtrim(trim($baseUrl), '/');
        if ($trimmed === '') {
            return self::DEFAULT_BASE_URL;
        }

        $path = parse_url($trimmed, PHP_URL_PATH);
        if ($path === null || $path === false || $path === '') {
            return $trimmed . self::API_PATH_SUFFIX;
        }

        return $trimmed;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->apiKey,
            'User-Agent' => self::USER_AGENT,
        ];
    }

    /**
     * @param array<string, mixed>|null $params
     */
    public function url(string $path, ?array $params = null): string
    {
        $url = str_contains($path, '://') ? $path : $this->baseUrl . '/' . ltrim($path, '/');

        $pairs = [];
        foreach ($params ?? [] as $key => $value) {
            if ($value === null) {
                continue;
            }
            // A list repeats the bare parameter; http_build_query would index
            // it as `key[0]=`, which the API reads as a different name.
            foreach (is_array($value) ? $value : [$value] as $item) {
                if (is_bool($item)) {
                    $item = $item ? 'true' : 'false';
                }
                // urlencode, not rawurlencode: http_build_query spelt a space
                // as '+', and the existing callers' URLs are asserted that way.
                $pairs[] = urlencode((string) $key) . '=' . urlencode((string) $item);
            }
        }

        if ($pairs !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . implode('&', $pairs);
        }

        return $url;
    }

    /**
     * Send a request, retrying on 429, and return the decoded body.
     *
     * @param array<string, mixed>|null $params
     */
    public function request(string $method, string $path, mixed $json = null, ?array $params = null): mixed
    {
        $url = $this->url($path, $params);
        $payload = $json === null ? null : self::encode($json);

        $attempt = 0;
        while (true) {
            $attempt++;
            $response = $this->transport->send($method, $url, $this->headers(), $payload);

            if ($response->status === 429 && $attempt < $this->maxRetries) {
                $wait = self::retryAfterSeconds($response);
                ($this->sleeper)(min($wait ?? 1.0, self::MAX_RETRY_WAIT));
                continue;
            }

            return $this->decode($response);
        }
    }

    /** @param array<string, mixed>|null $params */
    public function get(string $path, ?array $params = null): mixed
    {
        return $this->request('GET', $path, null, $params);
    }

    public function post(string $path, mixed $json = null): mixed
    {
        return $this->request('POST', $path, $json);
    }

    public function put(string $path, mixed $json = null): mixed
    {
        return $this->request('PUT', $path, $json);
    }

    public function patch(string $path, mixed $json = null): mixed
    {
        return $this->request('PATCH', $path, $json);
    }

    public function delete(string $path, mixed $json = null): mixed
    {
        return $this->request('DELETE', $path, $json);
    }

    /**
     * Send a request to an arbitrary URL with only the given headers: no API
     * key, no JSON, no retry. Used for presigned uploads to storage.
     *
     * @param array<string, string> $headers
     */
    public function sendRaw(string $method, string $url, array $headers, ?string $body): Response
    {
        $response = $this->transport->send($method, $url, $headers, $body);
        if (!$response->isSuccess()) {
            $decoded = json_decode($response->body, true);

            throw ErrorFactory::fromResponse(
                $response->status,
                json_last_error() === JSON_ERROR_NONE ? $decoded : $response->body,
                self::retryAfterSeconds($response),
            );
        }

        return $response;
    }

    private function decode(Response $response): mixed
    {
        $body = null;
        if ($response->status !== 204 && $response->body !== '') {
            $decoded = json_decode($response->body, true);
            $body = json_last_error() === JSON_ERROR_NONE ? $decoded : $response->body;
        }

        if ($response->isSuccess()) {
            if (is_string($body)) {
                $contentType = $response->header('content-type') ?? 'no content type';

                throw new ApiException(
                    "Expected a JSON response, got {$contentType}",
                    $response->status,
                    null,
                    $body,
                );
            }

            return $body;
        }

        if ($response->status === 429) {
            throw new RateLimitException(
                self::messageOf($body, 429),
                429,
                self::codeOf($body),
                $body,
                self::retryAfterSeconds($response),
            );
        }

        throw ErrorFactory::fromResponse($response->status, $body);
    }

    /** Retry-After is either delta seconds or an HTTP date. */
    public static function retryAfterSeconds(Response $response): ?float
    {
        $raw = $response->header('retry-after');
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $raw = trim($raw);

        if (is_numeric($raw)) {
            return max(0.0, (float) $raw);
        }

        $target = strtotime($raw);
        if ($target === false) {
            return null;
        }

        return max(0.0, (float) ($target - time()));
    }

    /**
     * Peel the {"data": ...} envelope the API wraps most responses in. Some
     * endpoints return the resource bare, so it is unwrapped only when present.
     */
    public static function unwrap(mixed $body): mixed
    {
        if (is_array($body) && array_key_exists('data', $body)) {
            return $body['data'];
        }

        return $body;
    }

    private static function encode(mixed $json): string
    {
        $encoded = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            throw new FopostException('fopost: could not encode the request body as JSON');
        }

        return $encoded;
    }

    private static function messageOf(mixed $body, int $status): string
    {
        if (is_array($body)) {
            if (isset($body['message']) && is_string($body['message']) && $body['message'] !== '') {
                return $body['message'];
            }
            if (isset($body['error']) && is_string($body['error']) && $body['error'] !== '') {
                return $body['error'];
            }
        }

        return "HTTP {$status}";
    }

    private static function codeOf(mixed $body): ?string
    {
        if (is_array($body) && isset($body['error']) && is_string($body['error'])) {
            return $body['error'];
        }

        return null;
    }
}
