<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class AccountsTest extends TestCase
{
    public function testListSendsTheCamelCaseWorkspaceParam(): void
    {
        $this->transport->push(200, ['data' => [
            ['id' => 'a_1', 'platform' => 'linkedin', 'username' => 'studio', 'isPrimary' => true],
        ]]);

        $accounts = $this->client()->accounts()->list('w_1');

        $this->assertSame('https://api.fopost.com/api/v1/accounts?workspaceId=w_1', $this->transport->last()['url']);
        $this->assertCount(1, $accounts);
        $this->assertSame('a_1', $accounts[0]->id);
        $this->assertTrue($accounts[0]->isPrimary);
    }

    public function testListWithoutAWorkspaceSendsNoQuery(): void
    {
        $this->transport->push(200, ['data' => []]);
        $this->client()->accounts()->list();

        $this->assertSame('https://api.fopost.com/api/v1/accounts', $this->transport->last()['url']);
    }

    public function testGetReturnsOneAccount(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'a_1', 'platform' => 'mastodon']]);

        $account = $this->client()->accounts()->get('a_1');

        $this->assertSame('https://api.fopost.com/api/v1/accounts/a_1', $this->transport->last()['url']);
        $this->assertSame('mastodon', $account->platform);
    }

    public function testDisconnectDeletesTheAccount(): void
    {
        $this->transport->push(200, ['data' => ['deleted' => true]]);

        $result = $this->client()->accounts()->disconnect('a_1');

        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/api/v1/accounts/a_1', $this->transport->last()['url']);
        $this->assertSame(['deleted' => true], $result);
    }

    public function testHealthReadsTheHealthEndpoint(): void
    {
        $this->transport->push(200, ['data' => ['status' => 'healthy']]);

        $health = $this->client()->accounts()->health('a_1');

        $this->assertSame('https://api.fopost.com/api/v1/accounts/a_1/health', $this->transport->last()['url']);
        $this->assertSame(['status' => 'healthy'], $health);
    }
}
