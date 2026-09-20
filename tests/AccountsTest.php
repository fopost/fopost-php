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

    public function testCreateTelegramConnectCode(): void
    {
        $this->transport->push(201, ['data' => [
            'code' => 'abc123',
            'command' => '/connect abc123',
            'bot_username' => 'fopost_bot',
            'deep_link' => 'https://t.me/fopost_bot?start=abc123',
            'group_link' => null,
            'expires_at' => '2026-09-19T12:15:00Z',
        ]]);

        $minted = $this->client()->accounts()->createTelegramConnectCode('w_1');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/accounts/telegram/connect-code', $this->transport->last()['url']);
        $this->assertSame(['workspaceId' => 'w_1'], $this->transport->lastJson());
        $this->assertSame('/connect abc123', $minted->command);
        $this->assertSame('fopost_bot', $minted->botUsername);
        $this->assertNull($minted->groupLink);
    }

    public function testCreateTelegramConnectCodeWithoutWorkspaceSendsAnEmptyObject(): void
    {
        $this->transport->push(201, ['data' => ['code' => 'c', 'command' => '/connect c']]);

        $this->client()->accounts()->createTelegramConnectCode();

        $this->assertSame('{}', $this->transport->last()['body']);
    }

    public function testGetTelegramConnectStatus(): void
    {
        $this->transport->push(200, ['data' => ['status' => 'failed', 'account_id' => null, 'reason' => 'slot_taken']]);

        $status = $this->client()->accounts()->getTelegramConnectStatus('abc123');

        $this->assertSame(
            'https://api.fopost.com/v1/accounts/telegram/connect-code/status?code=abc123',
            $this->transport->last()['url'],
        );
        $this->assertSame('failed', $status->status);
        $this->assertSame('slot_taken', $status->reason);
    }

    public function testTelegramBotCommandsRoutes(): void
    {
        $commands = [['command' => 'start', 'description' => 'Start the bot']];
        $url = 'https://api.fopost.com/v1/accounts/a_1/telegram/commands';

        $this->transport->push(200, ['data' => ['commands' => $commands]]);
        $listed = $this->client()->accounts()->getTelegramBotCommands('a_1');
        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame($url, $this->transport->last()['url']);
        $this->assertSame('start', $listed->commands[0]->command);

        $this->transport->push(200, ['data' => ['commands' => $commands]]);
        $set = $this->client()->accounts()->setTelegramBotCommands('a_1', $commands);
        $this->assertSame('PUT', $this->transport->last()['method']);
        $this->assertSame($url, $this->transport->last()['url']);
        $this->assertSame(['commands' => $commands], $this->transport->lastJson());
        $this->assertSame('Start the bot', $set->commands[0]->description);

        $this->transport->push(200, ['data' => ['commands' => []]]);
        $cleared = $this->client()->accounts()->deleteTelegramBotCommands('a_1');
        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame($url, $this->transport->last()['url']);
        $this->assertSame([], $cleared->commands);
    }

    public function testSlackChannelsAndMembers(): void
    {
        $this->transport->push(200, ['data' => [
            ['id' => 'C1', 'name' => 'general', 'is_private' => false, 'is_member' => true, 'is_current' => true],
        ]]);
        $channels = $this->client()->accounts()->listSlackChannels('a_1');
        $this->assertSame('https://api.fopost.com/v1/accounts/a_1/slack/channels', $this->transport->last()['url']);
        $this->assertSame('C1', $channels[0]->id);
        $this->assertTrue($channels[0]->isCurrent);

        $this->transport->push(200, ['data' => [
            ['id' => 'U1', 'name' => 'sam', 'real_name' => 'Sam Rivera', 'display_name' => null, 'is_bot' => false],
        ]]);
        $members = $this->client()->accounts()->listSlackMembers('a_1');
        $this->assertSame('https://api.fopost.com/v1/accounts/a_1/slack/members', $this->transport->last()['url']);
        $this->assertSame('U1', $members[0]->id);
        $this->assertSame('Sam Rivera', $members[0]->realName);
        $this->assertNull($members[0]->displayName);
    }

    public function testSlackIdentityGetAndPartialUpdate(): void
    {
        $url = 'https://api.fopost.com/v1/accounts/a_1/slack/identity';
        $identity = ['username' => 'Launch Bot', 'icon_url' => null, 'icon_emoji' => ':rocket:'];

        $this->transport->push(200, ['data' => $identity]);
        $read = $this->client()->accounts()->getSlackIdentity('a_1');
        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame($url, $this->transport->last()['url']);
        $this->assertSame(':rocket:', $read->iconEmoji);

        $this->transport->push(200, ['data' => $identity]);
        $updated = $this->client()->accounts()->updateSlackIdentity('a_1', username: 'Launch Bot', iconUrl: null);
        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertSame($url, $this->transport->last()['url']);
        // Omitted fields stay off the wire; null is sent to clear.
        $this->assertSame(['username' => 'Launch Bot', 'icon_url' => null], $this->transport->lastJson());
        $this->assertSame('Launch Bot', $updated->username);
    }

    public function testSlackWebhookConnectionIsAnApiException(): void
    {
        $this->transport->push(409, ['error' => 'webhook_connection', 'message' => 'Reconnect with the Slack app']);

        try {
            $this->client()->accounts()->listSlackChannels('a_1');
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            $this->assertSame(409, $e->getStatus());
            $this->assertSame('webhook_connection', $e->errorCode);
        }
    }

    public function testDiscordChannelsAndSwitch(): void
    {
        $this->transport->push(200, ['data' => [
            [
                'id' => 'c2',
                'name' => 'launches',
                'type' => 0,
                'parent_id' => null,
                'nsfw' => false,
                'can_post' => true,
                'is_current' => true,
            ],
        ]]);
        $channels = $this->client()->accounts()->listDiscordChannels('a_1');
        $this->assertSame('https://api.fopost.com/v1/accounts/a_1/discord/channels', $this->transport->last()['url']);
        $this->assertSame('c2', $channels[0]->id);
        $this->assertTrue($channels[0]->isCurrent);

        $this->transport->push(200, ['data' => ['id' => 'c2', 'name' => 'launches', 'is_current' => true]]);
        $this->client()->accounts()->switchDiscordChannel('a_1', 'c2');
        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertSame(['channel_id' => 'c2'], $this->transport->lastJson());
    }

    public function testDiscordIdentityPartialUpdate(): void
    {
        $this->transport->push(200, ['data' => ['username' => 'Release Bot', 'avatar_url' => null]]);
        $updated = $this->client()->accounts()->updateDiscordIdentity('a_1', username: 'Release Bot');
        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/accounts/a_1/discord/identity', $this->transport->last()['url']);
        // Omitted fields stay off the wire, so Discord keeps them.
        $this->assertSame(['username' => 'Release Bot'], $this->transport->lastJson());
        $this->assertSame('Release Bot', $updated->username);
    }

    public function testDiscordEventRoundTrip(): void
    {
        $event = [
            'id' => 'e1',
            'name' => 'Launch stream',
            'description' => null,
            'channel_id' => null,
            'location' => 'https://example.com/live',
            'start_time' => '2026-10-01T18:00:00.000Z',
            'end_time' => '2026-10-01T19:00:00.000Z',
            'status' => 'scheduled',
            'user_count' => 0,
        ];

        $this->transport->push(201, ['data' => $event]);
        $created = $this->client()->accounts()->createDiscordEvent(
            'a_1',
            name: 'Launch stream',
            startTime: '2026-10-01T18:00:00.000Z',
            endTime: '2026-10-01T19:00:00.000Z',
            location: 'https://example.com/live',
        );
        $this->assertSame('e1', $created->id);
        $this->assertSame([
            'name' => 'Launch stream',
            'start_time' => '2026-10-01T18:00:00.000Z',
            'end_time' => '2026-10-01T19:00:00.000Z',
            'location' => 'https://example.com/live',
        ], $this->transport->lastJson());

        $this->transport->push(200, ['data' => [$event]]);
        $this->assertSame('e1', $this->client()->accounts()->listDiscordEvents('a_1')[0]->id);

        $this->transport->push(200, ['data' => ['status' => 'canceled'] + $event]);
        $updated = $this->client()->accounts()->updateDiscordEvent('a_1', 'e1', status: 'canceled');
        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertSame(['status' => 'canceled'], $this->transport->lastJson());
        $this->assertSame('canceled', $updated->status);

        $this->transport->push(200, ['data' => ['deleted' => true]]);
        $this->client()->accounts()->deleteDiscordEvent('a_1', 'e1');
        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/accounts/a_1/discord/events/e1', $this->transport->last()['url']);
    }

    public function testDiscordMembersRolesAndDm(): void
    {
        $this->transport->push(200, ['data' => [
            ['id' => 'u7', 'username' => 'ada', 'is_bot' => false, 'roles' => ['r1']],
        ]]);
        $members = $this->client()->accounts()->listDiscordMembers('a_1', query: 'ada');
        $this->assertStringContainsString('q=ada', $this->transport->last()['url']);
        $this->assertSame('u7', $members[0]->id);
        $this->assertSame(['r1'], $members[0]->roles);

        $this->transport->push(200, ['data' => ['assigned' => true]]);
        $this->client()->accounts()->addDiscordMemberRole('a_1', 'r1', 'u7');
        $this->assertSame('PUT', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a_1/discord/roles/r1/members/u7',
            $this->transport->last()['url'],
        );

        $this->transport->push(201, ['data' => ['id' => 'm1', 'channel_id' => 'dm1']]);
        $sent = $this->client()->accounts()->sendDiscordDm('a_1', 'u7', 'hi');
        $this->assertSame('dm1', $sent->channelId);
        $this->assertSame(['member_id' => 'u7', 'content' => 'hi'], $this->transport->lastJson());
    }

    public function testDiscordWebhookConnectionIsAnApiException(): void
    {
        $this->transport->push(409, ['error' => 'webhook_connection', 'message' => 'Upgrade it to the bot first']);

        try {
            $this->client()->accounts()->listDiscordChannels('a_1');
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            $this->assertSame(409, $e->getStatus());
            $this->assertSame('webhook_connection', $e->errorCode);
        }
    }
}
