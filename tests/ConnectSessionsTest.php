<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class ConnectSessionsTest extends TestCase
{
    public function testCreateAndGetFollowTheSession(): void
    {
        $this->transport->push(201, [
            'id' => 'cs_1',
            'status' => 'pending',
            'external_user_id' => 'user-42',
            'platforms' => ['linkedin'],
            'redirect_url' => 'https://yourbrand.com/connected',
            'branding' => ['name' => 'Your Brand', 'logo_url' => null],
            'account_id' => null,
            'url' => 'https://app.fopost.com/connect/cs_1',
            'expires_at' => '2026-03-01T10:00:00Z',
            'used_at' => null,
            'created_at' => '2026-03-01T09:00:00Z',
        ]);
        $this->transport->push(200, [
            'id' => 'cs_1',
            'status' => 'used',
            'external_user_id' => 'user-42',
            'platforms' => ['linkedin'],
            'account_id' => 'a_1',
            'expires_at' => '2026-03-01T10:00:00Z',
            'used_at' => '2026-03-01T09:30:00Z',
            'created_at' => '2026-03-01T09:00:00Z',
        ]);

        $session = $this->client()->connectSessions()->create(
            'user-42',
            ['linkedin'],
            workspaceId: 'w_1',
            redirectUrl: 'https://yourbrand.com/connected',
            branding: ['name' => 'Your Brand'],
        );
        $used = $this->client()->connectSessions()->get('cs_1');

        $this->assertSame('POST', $this->transport->requests[0]['method']);
        $this->assertSame('https://api.fopost.com/v1/connect-sessions', $this->transport->requests[0]['url']);
        $this->assertSame([
            'workspaceId' => 'w_1',
            'externalUserId' => 'user-42',
            'platforms' => ['linkedin'],
            'redirectUrl' => 'https://yourbrand.com/connected',
            'branding' => ['name' => 'Your Brand'],
        ], json_decode((string) $this->transport->requests[0]['body'], true));
        $this->assertSame('https://app.fopost.com/connect/cs_1', $session->url);
        $this->assertSame('pending', $session->status);
        $this->assertSame(['linkedin'], $session->platforms);

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/connect-sessions/cs_1', $this->transport->last()['url']);
        $this->assertSame('used', $used->status);
        $this->assertSame('a_1', $used->accountId);
        $this->assertNull($used->url);
    }
}
