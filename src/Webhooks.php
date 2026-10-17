<?php

declare(strict_types=1);

namespace Fopost\Sdk;

use Fopost\Sdk\Exception\WebhookSignatureException;
use Fopost\Sdk\Model\WebhookEvent;

/**
 * Verifies a FoPost webhook delivery and returns its envelope.
 *
 *     $event = Webhooks::verify(file_get_contents('php://input'), getallheaders(), $secret);
 *     if ($event->event === Blog::ARTICLE_PUBLISHED_EVENT) {
 *         $article = $event->article();
 *     }
 *
 * The API signs the exact bytes it sends, so pass the raw request body and
 * never a re-encoded array. Two signatures travel with every delivery:
 * X-FoPost-Signature-256 over "<timestamp>.<body>" beside X-FoPost-Timestamp,
 * and the older X-FoPost-Signature over the body alone. The timestamped one is
 * checked whenever the timestamp header is present and refused past
 * TOLERANCE_SECONDS; the plain one is accepted only when it is not.
 */
final class Webhooks
{
    public const SIGNATURE_HEADER = 'X-FoPost-Signature';
    public const TIMESTAMPED_SIGNATURE_HEADER = 'X-FoPost-Signature-256';
    public const TIMESTAMP_HEADER = 'X-FoPost-Timestamp';
    public const EVENT_HEADER = 'X-FoPost-Event';
    public const DELIVERY_HEADER = 'X-FoPost-Delivery';
    public const TOLERANCE_SECONDS = 300;

    /**
     * @param array<string, string|array<int, string>> $headers as received, any casing
     * @param int|null $now unix seconds; defaults to time()
     * @throws WebhookSignatureException
     */
    public static function verify(string $rawBody, array $headers, string $secret, ?int $now = null): WebhookEvent
    {
        if ($secret === '') {
            throw new WebhookSignatureException('fopost: a webhook secret is required', 'missing_secret');
        }

        $timestamp = self::header($headers, self::TIMESTAMP_HEADER);
        if ($timestamp !== null) {
            self::verifyTimestamped($rawBody, $headers, $secret, $timestamp, $now ?? time());
        } else {
            self::verifyPlain($rawBody, $headers, $secret);
        }

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded) || !isset($decoded['event']) || !is_string($decoded['event'])) {
            throw new WebhookSignatureException('fopost: the webhook body is not an event envelope', 'invalid_payload');
        }

        return WebhookEvent::fromArray($decoded);
    }

    /** The X-FoPost-Signature value the API sends for these bytes. */
    public static function sign(string $rawBody, string $secret): string
    {
        return 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
    }

    /** The X-FoPost-Signature-256 value the API sends for these bytes at this timestamp. */
    public static function signTimestamped(string $rawBody, string $secret, int $timestamp): string
    {
        return 'sha256=' . hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);
    }

    /** @param array<string, string|array<int, string>> $headers */
    private static function verifyTimestamped(
        string $rawBody,
        array $headers,
        string $secret,
        string $timestamp,
        int $now,
    ): void {
        if (!ctype_digit($timestamp)) {
            throw new WebhookSignatureException('fopost: the webhook timestamp is not a number', 'invalid_timestamp');
        }
        if (abs($now - (int) $timestamp) > self::TOLERANCE_SECONDS) {
            throw new WebhookSignatureException('fopost: the webhook timestamp is out of tolerance', 'stale_timestamp');
        }

        $given = self::header($headers, self::TIMESTAMPED_SIGNATURE_HEADER);
        if ($given === null) {
            throw new WebhookSignatureException(
                'fopost: the webhook carries no timestamped signature',
                'missing_signature',
            );
        }
        if (!hash_equals(self::signTimestamped($rawBody, $secret, (int) $timestamp), trim($given))) {
            throw new WebhookSignatureException('fopost: the webhook signature does not match', 'invalid_signature');
        }
    }

    /** @param array<string, string|array<int, string>> $headers */
    private static function verifyPlain(string $rawBody, array $headers, string $secret): void
    {
        $given = self::header($headers, self::SIGNATURE_HEADER);
        if ($given === null) {
            throw new WebhookSignatureException('fopost: the webhook carries no signature', 'missing_signature');
        }
        if (!hash_equals(self::sign($rawBody, $secret), trim($given))) {
            throw new WebhookSignatureException('fopost: the webhook signature does not match', 'invalid_signature');
        }
    }

    /** @param array<string, string|array<int, string>> $headers */
    private static function header(array $headers, string $name): ?string
    {
        $wanted = strtolower($name);
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) !== $wanted) {
                continue;
            }
            $value = is_array($value) ? ($value[0] ?? null) : $value;

            return is_string($value) && $value !== '' ? $value : null;
        }

        return null;
    }
}
