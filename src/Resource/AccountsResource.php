<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\AccountMove;
use Fopost\Sdk\Model\AccountPlatformMetrics;
use Fopost\Sdk\Model\AccountRename;
use Fopost\Sdk\Model\DiscordChannel;
use Fopost\Sdk\Model\DiscordIdentity;
use Fopost\Sdk\Model\DiscordMember;
use Fopost\Sdk\Model\DiscordMessage;
use Fopost\Sdk\Model\DiscordMessageRef;
use Fopost\Sdk\Model\DiscordRole;
use Fopost\Sdk\Model\DiscordScheduledEvent;
use Fopost\Sdk\Model\DiscordThread;
use Fopost\Sdk\Model\MetaGreeting;
use Fopost\Sdk\Model\MetaGreetingText;
use Fopost\Sdk\Model\MetaIceBreaker;
use Fopost\Sdk\Model\MetaIceBreakers;
use Fopost\Sdk\Model\MetaPersistentMenu;
use Fopost\Sdk\Model\BlueskyLanguages;
use Fopost\Sdk\Model\InstagramAudio;
use Fopost\Sdk\Model\InstagramPublishingLimit;
use Fopost\Sdk\Model\InstagramStory;
use Fopost\Sdk\Model\InstagramStoryInsights;
use Fopost\Sdk\Model\LinkedInMention;
use Fopost\Sdk\Model\PinterestBoard;
use Fopost\Sdk\Model\SlackChannel;
use Fopost\Sdk\Model\SlackIdentity;
use Fopost\Sdk\Model\SlackMember;
use Fopost\Sdk\Model\SocialAccount;
use Fopost\Sdk\Model\TelegramBotCommand;
use Fopost\Sdk\Model\TelegramBotCommands;
use Fopost\Sdk\Model\TelegramConnectCode;
use Fopost\Sdk\Model\TelegramConnectStatus;
use Fopost\Sdk\Model\WebhookSubscription;
use Fopost\Sdk\Model\TikTokCreatorInfo;
use Fopost\Sdk\Model\TikTokMusic;
use Fopost\Sdk\Model\TikTokPlace;
use Fopost\Sdk\Model\TikTokVideoSource;
use Fopost\Sdk\Model\YouTubeCaptionTrack;
use Fopost\Sdk\Model\YouTubePlaylist;
use Fopost\Sdk\Model\YouTubeTranscript;
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

    /**
     * The numbers only this account's network reports, in its own vocabulary.
     *
     * Ad-break earnings, story taps, a retention curve, the search terms behind a
     * listing — keyed by the platform's own metric names, read from the newest
     * collected snapshot rather than fetched live. Needs the `analytics` scope.
     *
     * A network whose metric access has not been granted yet answers 503
     * (`platform_metrics_unavailable`) rather than an empty set.
     */
    public function platformMetrics(string $accountId): AccountPlatformMetrics
    {
        return AccountPlatformMetrics::fromArray(
            self::unwrap($this->http->get("/accounts/{$accountId}/insights", ['raw' => 'true'])),
        );
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

    // ─── Meta messaging settings (Facebook Pages, Instagram) ─────────

    /** The prompts shown before the first message; networks without them answer 400. */
    public function getIceBreakers(string $accountId): MetaIceBreakers
    {
        return MetaIceBreakers::fromArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/messaging/ice-breakers"),
        ));
    }

    /**
     * Replace the ice breakers. Up to four.
     *
     * @param array<int, MetaIceBreaker|array{question: string, payload: string}> $iceBreakers
     */
    public function setIceBreakers(string $accountId, array $iceBreakers): MetaIceBreakers
    {
        $payload = array_map(
            static fn (MetaIceBreaker|array $b): array => $b instanceof MetaIceBreaker
                ? ['question' => $b->question, 'payload' => $b->payload]
                : ['question' => $b['question'], 'payload' => $b['payload']],
            array_values($iceBreakers),
        );

        return MetaIceBreakers::fromArray(self::unwrap(
            $this->http->put("/accounts/{$accountId}/messaging/ice-breakers", ['ice_breakers' => $payload]),
        ));
    }

    public function deleteIceBreakers(string $accountId): MetaIceBreakers
    {
        return MetaIceBreakers::fromArray(self::unwrap(
            $this->http->delete("/accounts/{$accountId}/messaging/ice-breakers"),
        ));
    }

    /** The always-visible Messenger menu. Facebook Pages only. */
    public function getPersistentMenu(string $accountId): MetaPersistentMenu
    {
        return MetaPersistentMenu::fromArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/messaging/persistent-menu"),
        ));
    }

    /**
     * Replace the menu, one entry per locale, up to three items each.
     *
     * @param array<int, array<string, mixed>> $menu
     */
    public function setPersistentMenu(string $accountId, array $menu): MetaPersistentMenu
    {
        return MetaPersistentMenu::fromArray(self::unwrap(
            $this->http->put(
                "/accounts/{$accountId}/messaging/persistent-menu",
                ['persistent_menu' => array_values($menu)],
            ),
        ));
    }

    public function deletePersistentMenu(string $accountId): MetaPersistentMenu
    {
        return MetaPersistentMenu::fromArray(self::unwrap(
            $this->http->delete("/accounts/{$accountId}/messaging/persistent-menu"),
        ));
    }

    /** The text shown before a Messenger conversation starts. Facebook Pages only. */
    public function getGreeting(string $accountId): MetaGreeting
    {
        return MetaGreeting::fromArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/messaging/greeting"),
        ));
    }

    /**
     * Replace the greeting, one entry per locale, each up to 160 characters.
     *
     * @param array<int, MetaGreetingText|array{locale?: string, text: string}> $greeting
     */
    public function setGreeting(string $accountId, array $greeting): MetaGreeting
    {
        $payload = array_map(
            static fn (MetaGreetingText|array $g): array => $g instanceof MetaGreetingText
                ? ['locale' => $g->locale, 'text' => $g->text]
                : ['locale' => $g['locale'] ?? 'default', 'text' => $g['text']],
            array_values($greeting),
        );

        return MetaGreeting::fromArray(self::unwrap(
            $this->http->put("/accounts/{$accountId}/messaging/greeting", ['greeting' => $payload]),
        ));
    }

    public function deleteGreeting(string $accountId): MetaGreeting
    {
        return MetaGreeting::fromArray(self::unwrap(
            $this->http->delete("/accounts/{$accountId}/messaging/greeting"),
        ));
    }

    /** What the network is delivering to the FoPost webhook for this account. */
    public function getWebhookSubscription(string $accountId): WebhookSubscription
    {
        return WebhookSubscription::fromArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/webhook-subscription"),
        ));
    }

    /** Subscribe to every field this account needs, lapsed or not. */
    public function resubscribeWebhook(string $accountId): WebhookSubscription
    {
        return WebhookSubscription::fromArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/webhook-subscription"),
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
    // --- Per-network extras -------------------------------------------

    /**
     * Boards this Pinterest connection can pin to.
     *
     * @return array<int, PinterestBoard>
     */
    public function listPinterestBoards(string $accountId): array
    {
        return PinterestBoard::listFrom(self::unwrap($this->http->get("/accounts/{$accountId}/pinterest/boards")));
    }

    /** $privacy is PUBLIC, PROTECTED or SECRET. */
    public function createPinterestBoard(
        string $accountId,
        string $name,
        ?string $description = null,
        ?string $privacy = null,
    ): PinterestBoard {
        $body = ['name' => $name];
        if ($description !== null) {
            $body['description'] = $description;
        }
        if ($privacy !== null) {
            $body['privacy'] = $privacy;
        }

        return PinterestBoard::fromArray(
            self::unwrap($this->http->post("/accounts/{$accountId}/pinterest/boards", $body)),
        );
    }

    /**
     * The channel's own playlists, with the stored default marked.
     *
     * @return array<int, YouTubePlaylist>
     */
    public function listYouTubePlaylists(string $accountId): array
    {
        return YouTubePlaylist::listFrom(self::unwrap($this->http->get("/accounts/{$accountId}/youtube/playlists")));
    }

    public function createYouTubePlaylist(
        string $accountId,
        string $title,
        ?string $description = null,
        ?string $privacy = null,
    ): YouTubePlaylist {
        $body = ['title' => $title];
        if ($description !== null) {
            $body['description'] = $description;
        }
        if ($privacy !== null) {
            $body['privacy'] = $privacy;
        }

        return YouTubePlaylist::fromArray(
            self::unwrap($this->http->post("/accounts/{$accountId}/youtube/playlists", $body)),
        );
    }

    /** The playlist a new video joins when the post picks none; null clears it. */
    public function setDefaultYouTubePlaylist(string $accountId, ?string $playlistId): ?string
    {
        $data = self::unwrap(
            $this->http->put("/accounts/{$accountId}/youtube/playlists/default", ['playlist_id' => $playlistId]),
        );
        $stored = is_array($data) ? ($data['playlist_id'] ?? null) : null;

        return is_string($stored) ? $stored : null;
    }

    /** @return array<int, YouTubeCaptionTrack> */
    public function listYouTubeCaptions(string $accountId, string $videoId): array
    {
        return YouTubeCaptionTrack::listFrom(
            self::unwrap($this->http->get("/accounts/{$accountId}/youtube/videos/{$videoId}/captions")),
        );
    }

    /** $body is the subtitle file itself; YouTube reads SRT and WebVTT and sniffs which. */
    public function uploadYouTubeCaptions(
        string $accountId,
        string $videoId,
        string $language,
        string $body,
        ?string $name = null,
        ?bool $isDraft = null,
    ): YouTubeCaptionTrack {
        $payload = ['language' => $language, 'body' => $body];
        if ($name !== null) {
            $payload['name'] = $name;
        }
        if ($isDraft !== null) {
            $payload['is_draft'] = $isDraft;
        }

        return YouTubeCaptionTrack::fromArray(
            self::unwrap($this->http->post("/accounts/{$accountId}/youtube/videos/{$videoId}/captions", $payload)),
        );
    }

    public function readYouTubeTranscript(string $accountId, string $captionId): YouTubeTranscript
    {
        return YouTubeTranscript::fromArray(
            self::unwrap($this->http->get("/accounts/{$accountId}/youtube/captions/{$captionId}")),
        );
    }

    /** What a post from this connection is written in when it does not say. */
    public function getBlueskyLanguages(string $accountId): BlueskyLanguages
    {
        return BlueskyLanguages::fromArray(
            self::unwrap($this->http->get("/accounts/{$accountId}/bluesky/languages")),
        );
    }

    /**
     * Up to three BCP-47 tags; an empty list clears the default.
     *
     * @param array<int, string> $languages
     */
    public function setBlueskyLanguages(string $accountId, array $languages): BlueskyLanguages
    {
        return BlueskyLanguages::fromArray(
            self::unwrap(
                $this->http->put("/accounts/{$accountId}/bluesky/languages", ['languages' => array_values($languages)]),
            ),
        );
    }

    /** The switches TikTok enforces at publish time, changed in the TikTok app. */
    public function getTikTokCreatorInfo(string $accountId): TikTokCreatorInfo
    {
        return TikTokCreatorInfo::fromArray(
            self::unwrap($this->http->get("/accounts/{$accountId}/tiktok/creator-info")),
        );
    }

    /**
     * TikTok's Commercial Music Library.
     *
     * Needs the Marketing API product on the TikTok app; without it the call
     * fails with 403 rather than answering an empty list.
     *
     * @return array<int, TikTokMusic>
     */
    public function searchTikTokMusic(string $accountId, string $q, ?int $limit = null): array
    {
        return TikTokMusic::listFrom(
            self::unwrap($this->http->get("/accounts/{$accountId}/tiktok/music", ['q' => $q, 'limit' => $limit])),
        );
    }

    /**
     * Places a post can be tagged with. Same TikTok product as the music library.
     *
     * @return array<int, TikTokPlace>
     */
    public function searchTikTokLocations(string $accountId, string $q, ?int $limit = null): array
    {
        return TikTokPlace::listFrom(
            self::unwrap($this->http->get("/accounts/{$accountId}/tiktok/locations", ['q' => $q, 'limit' => $limit])),
        );
    }

    /** Resolve a share link to one of this account's own videos, for repurposing. */
    public function lookupTikTokVideo(string $accountId, string $url): TikTokVideoSource
    {
        return TikTokVideoSource::fromArray(
            self::unwrap($this->http->post("/accounts/{$accountId}/tiktok/video-download", ['url' => $url])),
        );
    }

    /**
     * Tracks a Reel can carry; with no query Instagram answers with what is trending.
     *
     * @return array<int, InstagramAudio>
     */
    public function searchInstagramAudio(string $accountId, ?string $q = null, ?string $audioType = null): array
    {
        $params = ['q' => $q, 'audio_type' => $audioType];

        return InstagramAudio::listFrom(
            self::unwrap($this->http->get("/accounts/{$accountId}/instagram/audio", $params)),
        );
    }

    /** How many posts are left before Instagram refuses the next one. */
    public function getInstagramPublishingLimit(string $accountId): InstagramPublishingLimit
    {
        return InstagramPublishingLimit::fromArray(
            self::unwrap($this->http->get("/accounts/{$accountId}/instagram/publishing-limit")),
        );
    }

    /**
     * Stories still inside their 24 hours, posted through FoPost or not.
     *
     * @return array<int, InstagramStory>
     */
    public function listInstagramStories(string $accountId, ?bool $insights = null): array
    {
        return InstagramStory::listFrom(
            self::unwrap($this->http->get("/accounts/{$accountId}/instagram/stories", ['insights' => $insights])),
        );
    }

    public function getInstagramStoryInsights(string $accountId, string $storyId): InstagramStoryInsights
    {
        return InstagramStoryInsights::fromArray(
            self::unwrap($this->http->get("/accounts/{$accountId}/instagram/stories/{$storyId}/insights")),
        );
    }

    /**
     * Organizations a LinkedIn post can mention. People are not searchable.
     *
     * @return array<int, LinkedInMention>
     */
    public function searchLinkedInMentions(string $accountId, string $q): array
    {
        return LinkedInMention::listFrom(
            self::unwrap($this->http->get("/accounts/{$accountId}/linkedin/mentions", ['q' => $q])),
        );
    }
}
