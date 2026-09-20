<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\AccountMove;
use Fopost\Sdk\Model\AccountRename;
use Fopost\Sdk\Model\MetaGreeting;
use Fopost\Sdk\Model\MetaGreetingText;
use Fopost\Sdk\Model\MetaIceBreaker;
use Fopost\Sdk\Model\MetaIceBreakers;
use Fopost\Sdk\Model\MetaPersistentMenu;
use Fopost\Sdk\Model\SlackChannel;
use Fopost\Sdk\Model\SlackIdentity;
use Fopost\Sdk\Model\SlackMember;
use Fopost\Sdk\Model\SocialAccount;
use Fopost\Sdk\Model\TelegramBotCommand;
use Fopost\Sdk\Model\TelegramBotCommands;
use Fopost\Sdk\Model\TelegramConnectCode;
use Fopost\Sdk\Model\TelegramConnectStatus;
use Fopost\Sdk\Model\WebhookSubscription;
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
}
