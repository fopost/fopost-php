<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\AccountMove;
use Fopost\Sdk\Model\AccountRename;
use Fopost\Sdk\Model\SocialAccount;

/** $client->accounts(): the social accounts connected to a workspace. */
final class AccountsResource extends Resource
{
    /**
     * Connected accounts, across every workspace unless one is named.
     *
     * @return array<int, SocialAccount>
     */
    public function list(?string $workspaceId = null, ?string $groupId = null): array
    {
        // This endpoint reads a camelCase workspaceId; posts and labels use snake.
        return SocialAccount::listFrom(
            self::unwrap($this->http->get('/accounts', ['workspaceId' => $workspaceId, 'group_id' => $groupId])),
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

    /** Rename the account; null or an empty string restores the platform name. */
    public function update(string $accountId, ?string $displayName): AccountRename
    {
        return AccountRename::fromArray(self::unwrap(
            $this->http->request('PATCH', "/accounts/{$accountId}", ['display_name' => $displayName]),
        ));
    }

    /** Move the account to another workspace the caller owns. */
    public function move(string $accountId, string $workspaceId): AccountMove
    {
        return AccountMove::fromArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/move", ['workspace_id' => $workspaceId]),
        ));
    }
}
