<?php

declare(strict_types=1);

namespace Fopost\Sdk\Exception;

/** Maps an HTTP status plus a decoded body onto the most specific exception. */
final class ErrorFactory
{
    /** @var array<int, class-string<FopostException>> */
    private const BY_STATUS = [
        400 => ValidationException::class,
        401 => AuthenticationException::class,
        402 => PaymentRequiredException::class,
        403 => PermissionDeniedException::class,
        404 => NotFoundException::class,
        422 => ValidationException::class,
        429 => RateLimitException::class,
    ];

    public static function fromResponse(int $status, mixed $body, ?float $retryAfter = null): FopostException
    {
        $code = null;
        $message = "HTTP {$status}";

        if (is_array($body)) {
            if (isset($body['error']) && is_string($body['error'])) {
                $code = $body['error'];
            }
            if (isset($body['message']) && is_string($body['message']) && $body['message'] !== '') {
                $message = $body['message'];
            } elseif ($code !== null) {
                $message = $code;
            }
        } elseif (is_string($body) && trim($body) !== '') {
            $message = trim($body);
        }

        $class = self::BY_STATUS[$status] ?? ApiException::class;

        if ($class === RateLimitException::class) {
            return new RateLimitException($message, $status, $code, $body, $retryAfter);
        }

        return new $class($message, $status, $code, $body);
    }
}
