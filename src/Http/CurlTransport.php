<?php

declare(strict_types=1);

namespace Fopost\Sdk\Http;

use Fopost\Sdk\Exception\ApiException;

/** The default transport: ext-curl, one handle per request. */
final class CurlTransport implements Transport
{
    public function __construct(private readonly float $timeout = HttpClient::DEFAULT_TIMEOUT)
    {
    }

    public function send(string $method, string $url, array $headers, ?string $body): Response
    {
        $handle = curl_init();
        if ($handle === false) {
            throw new ApiException('fopost: could not initialise a curl handle');
        }

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = "{$name}: {$value}";
        }

        $responseHeaders = [];

        curl_setopt_array($handle, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_TIMEOUT_MS => (int) round($this->timeout * 1000),
            CURLOPT_CONNECTTIMEOUT_MS => (int) round($this->timeout * 1000),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_HEADERFUNCTION => static function ($_handle, string $line) use (&$responseHeaders): int {
                $length = strlen($line);
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return $length;
            },
        ]);

        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($handle);
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($raw === false || $errno !== 0) {
            throw new ApiException("fopost: request failed: {$error}", 0, 'transport_error');
        }

        return new Response($status, $responseHeaders, (string) $raw);
    }
}
