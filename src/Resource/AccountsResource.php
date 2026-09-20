<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\AccountMove;
use Fopost\Sdk\Model\AccountRename;
use Fopost\Sdk\Model\DiscordChannel;
use Fopost\Sdk\Model\DiscordIdentity;
use Fopost\Sdk\Model\DiscordMember;
use Fopost\Sdk\Model\DiscordMessage;
use Fopost\Sdk\Model\DiscordMessageRef;
use Fopost\Sdk\Model\DiscordRole;
use Fopost\Sdk\Model\DiscordScheduledEvent;
use Fopost\Sdk\Model\DiscordThread;
use Fopost\Sdk\Model\SlackChannel;
use Fopost\Sdk\Model\SlackIdentity;
use Fopost\Sdk\Model\SlackMember;
use Fopost\Sdk\Model\SocialAccount;
use Fopost\Sdk\Model\TelegramBotCommand;
use Fopost\Sdk\Model\TelegramBotCommands;
use Fopost\Sdk\Model\TelegramConnectCode;
use Fopost\Sdk\Model\TelegramConnectStatus;
use Fopost\Sdk\Undefined;

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

    /**
     * Mint a one-time code; send `/connect <code>` to the bot in a chat to connect it.
     * $workspaceId may be omitted for a key bound to one workspace.
     */
    public function createTelegramConnectCode(?string $workspaceId = null): TelegramConnectCode
    {
        $body = $workspaceId === null ? (object) [] : ['workspaceId' => $workspaceId];

        return TelegramConnectCode::fromArray(self::unwrap(
            $this->http->post('/accounts/telegram/connect-code', $body),
        ));
    }

    public function getTelegramConnectStatus(string $code): TelegramConnectStatus
    {
        return TelegramConnectStatus::fromArray(self::unwrap(
            $this->http->get('/accounts/telegram/connect-code/status', ['code' => $code]),
        ));
    }

    public function getTelegramBotCommands(string $accountId): TelegramBotCommands
    {
        return TelegramBotCommands::fromArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/telegram/commands"),
        ));
    }

    /**
     * Replace the bot's command menu for this chat.
     *
     * @param array<int, TelegramBotCommand|array{command: string, description: string}> $commands
     */
    public function setTelegramBotCommands(string $accountId, array $commands): TelegramBotCommands
    {
        $payload = array_map(
            static fn (TelegramBotCommand|array $c): array => $c instanceof TelegramBotCommand
                ? ['command' => $c->command, 'description' => $c->description]
                : ['command' => $c['command'], 'description' => $c['description']],
            array_values($commands),
        );

        return TelegramBotCommands::fromArray(self::unwrap(
            $this->http->put("/accounts/{$accountId}/telegram/commands", ['commands' => $payload]),
        ));
    }

    public function deleteTelegramBotCommands(string $accountId): TelegramBotCommands
    {
        return TelegramBotCommands::fromArray(self::unwrap(
            $this->http->delete("/accounts/{$accountId}/telegram/commands"),
        ));
    }

    /**
     * Channels the Slack app can post to in the connected workspace.
     *
     * @return array<int, SlackChannel>
     */
    public function listSlackChannels(string $accountId): array
    {
        return SlackChannel::listFrom(self::unwrap($this->http->get("/accounts/{$accountId}/slack/channels")));
    }

    /**
     * People in the connected Slack workspace; a member's id is the handle for starting a DM.
     *
     * @return array<int, SlackMember>
     */
    public function listSlackMembers(string $accountId): array
    {
        return SlackMember::listFrom(self::unwrap($this->http->get("/accounts/{$accountId}/slack/members")));
    }

    public function getSlackIdentity(string $accountId): SlackIdentity
    {
        return SlackIdentity::fromArray(self::unwrap($this->http->get("/accounts/{$accountId}/slack/identity")));
    }

    /** Set the posting name and icon: omitted keeps a field, null clears it. Pass one icon, not both. */
    public function updateSlackIdentity(
        string $accountId,
        string|null|Undefined $username = Undefined::Value,
        string|null|Undefined $iconUrl = Undefined::Value,
        string|null|Undefined $iconEmoji = Undefined::Value,
    ): SlackIdentity {
        $body = [];
        foreach (['username' => $username, 'icon_url' => $iconUrl, 'icon_emoji' => $iconEmoji] as $key => $value) {
            if (!Undefined::is($value)) {
                $body[$key] = $value;
            }
        }

        return SlackIdentity::fromArray(self::unwrap(
            $this->http->request('PATCH', "/accounts/{$accountId}/slack/identity", $body === [] ? (object) [] : $body),
        ));
    }

    // ── Discord (bot connections; a webhook one answers 409 webhook_connection) ──

    /**
     * Text channels the bot can post to in the connected server.
     *
     * @return array<int, DiscordChannel>
     */
    public function listDiscordChannels(string $accountId): array
    {
        return DiscordChannel::listFrom(self::unwrap($this->http->get("/accounts/{$accountId}/discord/channels")));
    }

    /** Move the account to another channel in the same server. */
    public function switchDiscordChannel(string $accountId, string $channelId): DiscordChannel
    {
        return DiscordChannel::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/accounts/{$accountId}/discord/channels/current",
            ['channel_id' => $channelId],
        )));
    }

    public function getDiscordIdentity(string $accountId): DiscordIdentity
    {
        return DiscordIdentity::fromArray(self::unwrap($this->http->get("/accounts/{$accountId}/discord/identity")));
    }

    /** Set the bot's nickname and avatar: omitted keeps a field, null clears it. */
    public function updateDiscordIdentity(
        string $accountId,
        string|null|Undefined $username = Undefined::Value,
        string|null|Undefined $avatarUrl = Undefined::Value,
    ): DiscordIdentity {
        $body = [];
        foreach (['username' => $username, 'avatar_url' => $avatarUrl] as $key => $value) {
            if (!Undefined::is($value)) {
                $body[$key] = $value;
            }
        }

        return DiscordIdentity::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/accounts/{$accountId}/discord/identity",
            $body === [] ? (object) [] : $body,
        )));
    }

    /**
     * Pinned messages in the account's channel.
     *
     * @return array<int, DiscordMessage>
     */
    public function listDiscordPins(string $accountId): array
    {
        return DiscordMessage::listFrom(self::unwrap(
            $this->http->get("/accounts/{$accountId}/discord/messages/pinned"),
        ));
    }

    public function deleteDiscordMessage(string $accountId, string $messageId): void
    {
        $this->http->delete("/accounts/{$accountId}/discord/messages/{$messageId}");
    }

    public function pinDiscordMessage(string $accountId, string $messageId): void
    {
        $this->http->post("/accounts/{$accountId}/discord/messages/{$messageId}/pin");
    }

    public function unpinDiscordMessage(string $accountId, string $messageId): void
    {
        $this->http->delete("/accounts/{$accountId}/discord/messages/{$messageId}/pin");
    }

    /** Publish an announcement-channel message to every server following it. */
    public function crosspostDiscordMessage(string $accountId, string $messageId): DiscordMessageRef
    {
        return DiscordMessageRef::fromArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/discord/messages/{$messageId}/crosspost"),
        ));
    }

    /** Start a thread on a message; the duration is 60, 1440, 4320 or 10080 minutes. */
    public function createDiscordThread(
        string $accountId,
        string $messageId,
        string $name,
        ?int $autoArchiveDuration = null,
    ): DiscordThread {
        $body = ['name' => $name];
        if ($autoArchiveDuration !== null) {
            $body['auto_archive_duration'] = $autoArchiveDuration;
        }

        return DiscordThread::fromArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/discord/messages/{$messageId}/thread", $body),
        ));
    }

    /** Send one message to a member of the server. */
    public function sendDiscordDm(string $accountId, string $memberId, string $content): DiscordMessageRef
    {
        return DiscordMessageRef::fromArray(self::unwrap($this->http->post(
            "/accounts/{$accountId}/discord/dm",
            ['member_id' => $memberId, 'content' => $content],
        )));
    }

    /**
     * The server's scheduled events.
     *
     * @return array<int, DiscordScheduledEvent>
     */
    public function listDiscordEvents(string $accountId): array
    {
        return DiscordScheduledEvent::listFrom(self::unwrap($this->http->get("/accounts/{$accountId}/discord/events")));
    }

    public function getDiscordEvent(string $accountId, string $eventId): DiscordScheduledEvent
    {
        return DiscordScheduledEvent::fromArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/discord/events/{$eventId}"),
        ));
    }

    /** Give $channelId (a voice or stage channel), or $location with an $endTime. */
    public function createDiscordEvent(
        string $accountId,
        string $name,
        string $startTime,
        ?string $endTime = null,
        ?string $description = null,
        ?string $channelId = null,
        ?string $location = null,
    ): DiscordScheduledEvent {
        $body = self::discordEventBody([
            'name' => $name,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'description' => $description,
            'channel_id' => $channelId,
            'location' => $location,
        ]);

        return DiscordScheduledEvent::fromArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/discord/events", $body),
        ));
    }

    /** Only the fields you name are sent; Discord keeps the rest. */
    public function updateDiscordEvent(
        string $accountId,
        string $eventId,
        ?string $name = null,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $description = null,
        ?string $channelId = null,
        ?string $location = null,
        ?string $status = null,
    ): DiscordScheduledEvent {
        $body = self::discordEventBody([
            'name' => $name,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'description' => $description,
            'channel_id' => $channelId,
            'location' => $location,
            'status' => $status,
        ]);

        return DiscordScheduledEvent::fromArray(self::unwrap(
            $this->http->request(
                'PATCH',
                "/accounts/{$accountId}/discord/events/{$eventId}",
                $body === [] ? (object) [] : $body,
            ),
        ));
    }

    public function deleteDiscordEvent(string $accountId, string $eventId): void
    {
        $this->http->delete("/accounts/{$accountId}/discord/events/{$eventId}");
    }

    /**
     * The server's roster, or the members matching $query by name prefix.
     *
     * @return array<int, DiscordMember>
     */
    public function listDiscordMembers(string $accountId, ?string $query = null, ?int $limit = null): array
    {
        return DiscordMember::listFrom(self::unwrap(
            $this->http->get("/accounts/{$accountId}/discord/members", ['q' => $query, 'limit' => $limit]),
        ));
    }

    public function getDiscordMember(string $accountId, string $memberId): DiscordMember
    {
        return DiscordMember::fromArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/discord/members/{$memberId}"),
        ));
    }

    /**
     * The server's roles, highest first.
     *
     * @return array<int, DiscordRole>
     */
    public function listDiscordRoles(string $accountId): array
    {
        return DiscordRole::listFrom(self::unwrap($this->http->get("/accounts/{$accountId}/discord/roles")));
    }

    public function createDiscordRole(
        string $accountId,
        string $name,
        ?int $color = null,
        ?bool $hoist = null,
        ?bool $mentionable = null,
        ?string $permissions = null,
    ): DiscordRole {
        $body = self::discordRoleBody($name, $color, $hoist, $mentionable, $permissions);

        return DiscordRole::fromArray(self::unwrap($this->http->post("/accounts/{$accountId}/discord/roles", $body)));
    }

    public function updateDiscordRole(
        string $accountId,
        string $roleId,
        ?string $name = null,
        ?int $color = null,
        ?bool $hoist = null,
        ?bool $mentionable = null,
        ?string $permissions = null,
    ): DiscordRole {
        $body = self::discordRoleBody($name, $color, $hoist, $mentionable, $permissions);

        return DiscordRole::fromArray(self::unwrap(
            $this->http->request(
                'PATCH',
                "/accounts/{$accountId}/discord/roles/{$roleId}",
                $body === [] ? (object) [] : $body,
            ),
        ));
    }

    public function deleteDiscordRole(string $accountId, string $roleId): void
    {
        $this->http->delete("/accounts/{$accountId}/discord/roles/{$roleId}");
    }

    public function addDiscordMemberRole(string $accountId, string $roleId, string $memberId): void
    {
        $this->http->request('PUT', "/accounts/{$accountId}/discord/roles/{$roleId}/members/{$memberId}");
    }

    public function removeDiscordMemberRole(string $accountId, string $roleId, string $memberId): void
    {
        $this->http->delete("/accounts/{$accountId}/discord/roles/{$roleId}/members/{$memberId}");
    }

    /**
     * @param array<string, string|null> $fields
     * @return array<string, string>
     */
    private static function discordEventBody(array $fields): array
    {
        return array_filter($fields, static fn (?string $value): bool => $value !== null);
    }

    /** @return array<string, mixed> */
    private static function discordRoleBody(
        ?string $name,
        ?int $color,
        ?bool $hoist,
        ?bool $mentionable,
        ?string $permissions,
    ): array {
        $body = [
            'name' => $name,
            'color' => $color,
            'hoist' => $hoist,
            'mentionable' => $mentionable,
            'permissions' => $permissions,
        ];

        return array_filter($body, static fn (mixed $value): bool => $value !== null);
    }
}
