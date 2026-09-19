<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\ApiException;

final class AccountsTest extends TestCase
{
    public function testListSendsTheCamelCaseWorkspaceParam(): void
    {
        $this->transport->push(200, ['data' => [
            ['id' => 'a_1', 'platform' => 'linkedin', 'username' => 'studio', 'isPrimary' => true],
        ]]);

        $accounts = $this->client()->accounts()->list('w_1');

        $this->assertSame('https://api.fopost.com/v1/accounts?workspaceId=w_1', $this->transport->last()['url']);
        $this->assertCount(1, $accounts);
        $this->assertSame('a_1', $accounts[0]->id);
        $this->assertTrue($accounts[0]->isPrimary);
    }

    public function testListWithoutAWorkspaceSendsNoQuery(): void
    {
        $this->transport->push(200, ['data' => []]);
        $this->client()->accounts()->list();

        $this->assertSame('https://api.fopost.com/v1/accounts', $this->transport->last()['url']);
    }

    public function testGetReturnsOneAccount(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'a_1', 'platform' => 'mastodon']]);

        $account = $this->client()->accounts()->get('a_1');

        $this->assertSame('https://api.fopost.com/v1/accounts/a_1', $this->transport->last()['url']);
        $this->assertSame('mastodon', $account->platform);
    }

    public function testDisconnectDeletesTheAccount(): void
    {
        $this->transport->push(200, ['data' => ['deleted' => true]]);

        $result = $this->client()->accounts()->disconnect('a_1');

        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/accounts/a_1', $this->transport->last()['url']);
        $this->assertSame(['deleted' => true], $result);
    }

    public function testHealthReadsTheHealthEndpoint(): void
    {
        $this->transport->push(200, ['data' => ['status' => 'healthy']]);

        $health = $this->client()->accounts()->health('a_1');

        $this->assertSame('https://api.fopost.com/v1/accounts/a_1/health', $this->transport->last()['url']);
        $this->assertSame(['status' => 'healthy'], $health);
    }

    public function testListFiltersByGroupAndReadsThePlatformName(): void
    {
        $this->transport->push(200, ['data' => [
            ['id' => 'a_1', 'platform' => 'linkedin', 'name' => 'Brand', 'platformName' => 'Studio'],
        ]]);

        $accounts = $this->client()->accounts()->list(groupId: 'g_1');

        $this->assertSame('https://api.fopost.com/v1/accounts?group_id=g_1', $this->transport->last()['url']);
        $this->assertSame('Brand', $accounts[0]->name);
        $this->assertSame('Studio', $accounts[0]->platformName);
    }

    public function testUpdatePatchesTheDisplayName(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'a_1', 'name' => 'Brand', 'platform_name' => 'Studio']]);

        $renamed = $this->client()->accounts()->update('a_1', 'Brand');

        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/accounts/a_1', $this->transport->last()['url']);
        $this->assertSame(['display_name' => 'Brand'], $this->transport->lastJson());
        $this->assertSame('Brand', $renamed->name);
        $this->assertSame('Studio', $renamed->platformName);
    }

    public function testUpdateWithNullSendsNullToRestoreThePlatformName(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'a_1', 'name' => 'Studio', 'platform_name' => 'Studio']]);

        $this->client()->accounts()->update('a_1', null);

        $this->assertSame(['display_name' => null], $this->transport->lastJson());
    }

    public function testMovePostsTheTargetWorkspace(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'a_1', 'workspace_id' => 'w_2']]);

        $moved = $this->client()->accounts()->move('a_1', 'w_2');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/accounts/a_1/move', $this->transport->last()['url']);
        $this->assertSame(['workspace_id' => 'w_2'], $this->transport->lastJson());
        $this->assertSame('w_2', $moved->workspaceId);
    }

    public function testMoveConflictKeepsTheBlockingTablesOnTheBody(): void
    {
        $this->transport->push(409, [
            'error' => 'move_blocked',
            'message' => 'Account has history',
            'blocking_tables' => ['posts'],
        ]);

        try {
            $this->client()->accounts()->move('a_1', 'w_2');
            $this->fail('expected an ApiException');
        } catch (ApiException $e) {
            $this->assertSame(409, $e->getStatus());
            $this->assertSame('move_blocked', $e->getErrorCode());
            $this->assertSame(['posts'], $e->getBody()['blocking_tables']);
        }
    }
}
