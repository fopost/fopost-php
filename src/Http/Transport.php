<?php

declare(strict_types=1);

namespace Fopost\Sdk\Http;

/** How the SDK puts a request on the wire. Swap it in tests. */
interface Transport
{
    /**
     * @param array<string, string> $headers
     */
    public function send(string $method, string $url, array $headers, ?string $body): Response;
}
