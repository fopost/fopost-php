<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class AccountGroupsTest extends TestCase
{
    private const GROUP = [
        'id' => 'g_1',
        'name' => 'Launch',
        'account_ids' => ['a_1', 'a_2'],
        'created_at' => '2026-09-01T10:00:00.000Z',
        'updated_at' => '2026-09-01T10:00:00.000Z',
    ];

    public function testListSendsTheWorkspaceParam(): void
    {
        $this->transport->push(200, ['data' => [self::GROUP]]);

        $groups = $this->client()->accountGroups()->list('w_1');

        $this->assertSame('https://api.fopost.com/v1/account-groups?workspace_id=w_1', $this->transport->last()['url']);
        $this->assertSame('g_1', $groups[0]->id);
        $this->assertSame(['a_1', 'a_2'], $groups[0]->accountIds);
        $this->assertNotNull($groups[0]->createdAt);
    }

    public function testCreatePostsTheGroup(): void
    {
        $this->transport->push(201, ['data' => self::GROUP]);

        $group = $this->client()->accountGroups()->create('w_1', 'Launch', ['a_1', 'a_2']);

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/account-groups', $this->transport->last()['url']);
        $this->assertSame(
            ['workspace_id' => 'w_1', 'name' => 'Launch', 'account_ids' => ['a_1', 'a_2']],
            $this->transport->lastJson(),
        );
        $this->assertSame('Launch', $group->name);
    }

    public function testCreateWithoutMembersOmitsAccountIds(): void
    {
        $this->transport->push(201, ['data' => self::GROUP]);

        $this->client()->accountGroups()->create('w_1', 'Launch');

        $this->assertSame(['workspace_id' => 'w_1', 'name' => 'Launch'], $this->transport->lastJson());
    }

    public function testGetReadsOneGroup(): void
    {
        $this->transport->push(200, ['data' => self::GROUP]);

        $group = $this->client()->accountGroups()->get('g_1');

        $this->assertSame('https://api.fopost.com/v1/account-groups/g_1', $this->transport->last()['url']);
        $this->assertSame('g_1', $group->id);
    }

    public function testUpdatePatchesTheName(): void
    {
        $this->transport->push(200, ['data' => ['name' => 'Renamed'] + self::GROUP]);

        $group = $this->client()->accountGroups()->update('g_1', 'Renamed');

        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/account-groups/g_1', $this->transport->last()['url']);
        $this->assertSame(['name' => 'Renamed'], $this->transport->lastJson());
        $this->assertSame('Renamed', $group->name);
    }

    public function testSetMembersReplacesTheMembers(): void
    {
        $this->transport->push(200, ['data' => ['account_ids' => ['a_3']] + self::GROUP]);

        $group = $this->client()->accountGroups()->setMembers('g_1', ['a_3']);

        $this->assertSame('PUT', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/account-groups/g_1/members', $this->transport->last()['url']);
        $this->assertSame(['account_ids' => ['a_3']], $this->transport->lastJson());
        $this->assertSame(['a_3'], $group->accountIds);
    }

    public function testDeleteRemovesTheGroup(): void
    {
        $this->transport->push(200, ['message' => 'Account group deleted']);

        $this->client()->accountGroups()->delete('g_1');

        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/account-groups/g_1', $this->transport->last()['url']);
    }
}
