<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\AccountGroup;

/** $client->accountGroups(): named sets of accounts to post to together. */
final class AccountGroupsResource extends Resource
{
    /** @return array<int, AccountGroup> */
    public function list(?string $workspaceId = null): array
    {
        return AccountGroup::listFrom(
            self::unwrap($this->http->get('/account-groups', ['workspace_id' => $workspaceId])),
        );
    }

    /** @param array<int, string>|null $accountIds */
    public function create(string $workspaceId, string $name, ?array $accountIds = null): AccountGroup
    {
        $body = self::compact([
            'workspace_id' => $workspaceId,
            'name' => $name,
            'account_ids' => $accountIds !== null ? array_values($accountIds) : null,
        ]);

        return AccountGroup::fromArray(self::unwrap($this->http->post('/account-groups', $body)));
    }

    public function get(string $groupId): AccountGroup
    {
        return AccountGroup::fromArray(self::unwrap($this->http->get("/account-groups/{$groupId}")));
    }

    /** Rename the group. */
    public function update(string $groupId, string $name): AccountGroup
    {
        return AccountGroup::fromArray(
            self::unwrap($this->http->request('PATCH', "/account-groups/{$groupId}", ['name' => $name])),
        );
    }

    public function delete(string $groupId): void
    {
        $this->http->delete("/account-groups/{$groupId}");
    }

    /**
     * Replace the group's members with exactly these accounts.
     *
     * @param array<int, string> $accountIds
     */
    public function setMembers(string $groupId, array $accountIds): AccountGroup
    {
        return AccountGroup::fromArray(self::unwrap(
            $this->http->put("/account-groups/{$groupId}/members", ['account_ids' => array_values($accountIds)]),
        ));
    }
}
