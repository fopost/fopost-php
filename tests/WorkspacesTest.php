<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class WorkspacesTest extends TestCase
{
    public function testListReturnsWorkspaces(): void
    {
        $this->transport->push(200, ['data' => [
            ['id' => 'w_1', 'name' => 'Studio', 'slug' => 'studio', 'requireApproval' => true],
        ]]);

        $workspaces = $this->client()->workspaces()->list();

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/workspaces', $this->transport->last()['url']);
        $this->assertCount(1, $workspaces);
        $this->assertSame('w_1', $workspaces[0]->id);
        $this->assertSame('Studio', $workspaces[0]->name);
        $this->assertTrue($workspaces[0]->requireApproval);
    }

    public function testGetReturnsOneWorkspaceWithItsAccounts(): void
    {
        $this->transport->push(200, ['data' => [
            'id' => 'w_1',
            'name' => 'Studio',
            'created_at' => '2026-01-05T10:00:00Z',
            'accounts' => [['id' => 'a_1', 'platform' => 'bluesky', 'username' => 'studio']],
        ]]);

        $workspace = $this->client()->workspaces()->get('w_1');

        $this->assertSame('https://api.fopost.com/v1/workspaces/w_1', $this->transport->last()['url']);
        $this->assertSame('w_1', $workspace->id);
        $this->assertSame('2026-01-05', $workspace->createdAt?->format('Y-m-d'));
        $this->assertCount(1, $workspace->accounts);
        $this->assertSame('bluesky', $workspace->accounts[0]->platform);
    }
}
