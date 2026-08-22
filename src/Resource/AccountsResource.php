<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\SocialAccount;

/** $client->accounts(): the social accounts connected to a workspace. */
final class AccountsResource extends Resource
{
    /**
     * Connected accounts, across every workspace unless one is named.
     *
     * @return array<int, SocialAccount>
     */
    public function list(?string $workspaceId = null): array
    {
        // This endpoint reads a camelCase query param; posts and labels use snake.
        return SocialAccount::listFrom(
            self::unwrap($this->http->get('/accounts', ['workspaceId' => $workspaceId])),
        );
    }

    public function get(string $accountId): SocialAccount
    {
        return SocialAccount::fromArray(self::unwrap($this->http->get("/accounts/{$accountId}")));
    }

    /**
     * Disconnect an account, revoking its stored credentials.
     *
     * @return array<string, mixed>
     */
    public function disconnect(string $accountId): array
    {
        return self::asArray(self::unwrap($this->http->delete("/accounts/{$accountId}")));
    }

    /**
     * Token validity and last check detail for one account.
     *
     * @return array<string, mixed>
     */
    public function health(string $accountId): array
    {
        return self::asArray(self::unwrap($this->http->get("/accounts/{$accountId}/health")));
    }
}
