<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\AccountMove;
use Fopost\Sdk\Model\AccountRename;
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
}
