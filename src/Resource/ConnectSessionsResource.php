<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\ConnectSession;

/**
 * $client->connectSessions(): hosted connect links for your own users.
 *
 * Create a session, send the user to its `url`, then poll `get()` until the
 * status is `used` and `accountId` names the connected account. Needs the
 * `accounts` scope.
 */
final class ConnectSessionsResource extends Resource
{
    /**
     * @param array<int, string> $platforms The networks the user may connect.
     * @param array{name?: string, logoUrl?: string}|null $branding
     */
    public function create(
        string $externalUserId,
        array $platforms,
        ?string $workspaceId = null,
        ?string $redirectUrl = null,
        ?array $branding = null,
    ): ConnectSession {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'externalUserId' => $externalUserId,
            'platforms' => array_values($platforms),
            'redirectUrl' => $redirectUrl,
            'branding' => $branding,
        ]);

        return ConnectSession::fromArray(self::unwrap($this->http->post('/connect-sessions', $body)));
    }

    public function get(string $sessionId): ConnectSession
    {
        return ConnectSession::fromArray(self::unwrap($this->http->get("/connect-sessions/{$sessionId}")));
    }
}
