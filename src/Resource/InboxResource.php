<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use DateTimeInterface;
use Fopost\Sdk\Model\InboxAccount;
use Fopost\Sdk\Model\InboxApproval;
use Fopost\Sdk\Model\InboxApprovalDecision;
use Fopost\Sdk\Model\InboxConversation;
use Fopost\Sdk\Model\InboxItem;
use Fopost\Sdk\Model\InboxPlatform;
use Fopost\Sdk\Model\InboxRefreshResult;
use Fopost\Sdk\Model\InboxReplyResult;
use Fopost\Sdk\Model\InboxStartConversationResult;
use Fopost\Sdk\Model\InboxThread;
use Fopost\Sdk\Model\Page;

/**
 * $client->inbox(): comments, mentions and direct messages on connected accounts.
 *
 * Every call needs the `inbox` scope.
 */
final class InboxResource extends Resource
{
    /**
     * One page of items, newest first.
     *
     * @return Page<InboxItem>
     */
    public function list(
        ?string $workspaceId = null,
        ?string $type = null,
        ?string $state = null,
        ?string $platform = null,
        ?string $accountId = null,
        ?string $postId = null,
        ?string $postExternalId = null,
        ?string $conversationId = null,
        ?string $direction = null,
        ?string $q = null,
        ?string $sort = null,
        int $page = 1,
        int $perPage = 25,
    ): Page {
        $body = $this->http->get('/inbox', [
            'workspace_id' => $workspaceId,
            'type' => $type,
            'state' => $state,
            'platform' => $platform,
            'account_id' => $accountId,
            'post_id' => $postId,
            'post_external_id' => $postExternalId,
            'conversation_id' => $conversationId,
            'direction' => $direction,
            'q' => $q,
            'sort' => $sort,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        return self::page(InboxItem::class, $body);
    }

    /**
     * One row per post with comments; pass kind `mentions` for posts we were tagged in.
     *
     * @return Page<InboxThread>
     */
    public function threads(
        ?string $workspaceId = null,
        ?string $kind = null,
        ?string $platform = null,
        ?string $accountId = null,
        ?string $state = null,
        ?string $q = null,
        ?string $sort = null,
        int $page = 1,
        int $perPage = 25,
    ): Page {
        $body = $this->http->get('/inbox/posts', [
            'workspace_id' => $workspaceId,
            'kind' => $kind,
            'platform' => $platform,
            'account_id' => $accountId,
            'state' => $state,
            'q' => $q,
            'sort' => $sort,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        return self::page(InboxThread::class, $body);
    }

    /**
     * One row per DM thread, latest first.
     *
     * @return Page<InboxConversation>
     */
    public function conversations(
        ?string $workspaceId = null,
        ?string $platform = null,
        ?string $accountId = null,
        ?string $state = null,
        ?string $q = null,
        ?string $sort = null,
        int $page = 1,
        int $perPage = 25,
    ): Page {
        $body = $this->http->get('/inbox/conversations', [
            'workspace_id' => $workspaceId,
            'platform' => $platform,
            'account_id' => $accountId,
            'state' => $state,
            'q' => $q,
            'sort' => $sort,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        return self::page(InboxConversation::class, $body);
    }

    public function unreadCount(?string $workspaceId = null): int
    {
        $body = $this->http->get('/inbox/unread-count', ['workspace_id' => $workspaceId]);

        return is_array($body) && is_int($body['count'] ?? null) ? $body['count'] : 0;
    }

    /**
     * Every active account, flagged with whether comments and DMs can be read for it.
     *
     * @return array<int, InboxAccount>
     */
    public function accounts(?string $workspaceId = null): array
    {
        return InboxAccount::listFrom(
            self::unwrap($this->http->get('/inbox/accounts', ['workspace_id' => $workspaceId])),
        );
    }

    /** @return array<int, InboxPlatform> */
    public function platforms(): array
    {
        return InboxPlatform::listFrom(self::unwrap($this->http->get('/inbox/platforms')));
    }

    /** Mark a whole comment thread or DM thread read. Returns how many items changed. */
    public function markThreadRead(
        string $workspaceId,
        string $accountId,
        ?string $postExternalId = null,
        ?string $conversationId = null,
    ): int {
        $body = self::compact([
            'workspace_id' => $workspaceId,
            'account_id' => $accountId,
            'post_external_id' => $postExternalId,
            'conversation_id' => $conversationId,
        ]);
        $result = self::unwrap($this->http->post('/inbox/read', $body));

        return is_array($result) && is_int($result['updated'] ?? null) ? $result['updated'] : 0;
    }

    /** Poll every inbox capable account in the workspace now. */
    public function refresh(string $workspaceId): InboxRefreshResult
    {
        return InboxRefreshResult::fromArray(
            self::unwrap($this->http->post('/inbox/refresh', ['workspace_id' => $workspaceId])),
        );
    }

    /** Mark the item `unread`, `read`, `resolved` or `snoozed`; `snoozed` needs a future snoozedUntil. */
    public function update(
        string $itemId,
        string $state,
        string|DateTimeInterface|null $snoozedUntil = null,
    ): InboxItem {
        $body = self::compact(['state' => $state, 'snoozedUntil' => self::iso($snoozedUntil)]);

        return InboxItem::fromArray(self::unwrap($this->http->request('PATCH', "/inbox/{$itemId}", $body)));
    }

    /** Edit our own comment on the platform, where canEdit is true. Needs the `publish` scope. */
    public function editComment(string $itemId, string $text): InboxItem
    {
        return InboxItem::fromArray(
            self::unwrap($this->http->request('PATCH', "/inbox/{$itemId}", ['text' => $text])),
        );
    }

    /**
     * Send the reply on the platform as the connected account.
     *
     * $text may be null when $mediaIds is given. $mediaIds and $quickReplies apply to DMs
     * and also need the `publish` scope.
     *
     * @param array<int, string>|null $mediaIds
     * @param array<int, string>|null $quickReplies
     */
    public function reply(
        string $itemId,
        ?string $text = null,
        ?array $mediaIds = null,
        ?array $quickReplies = null,
    ): InboxReplyResult {
        $body = self::compact([
            'text' => $text,
            'media_ids' => $mediaIds,
            'quick_replies' => $quickReplies,
        ]);

        return InboxReplyResult::fromArray(
            self::unwrap($this->http->post("/inbox/{$itemId}/reply", $body)),
        );
    }

    public function hide(string $itemId): InboxItem
    {
        return InboxItem::fromArray(self::unwrap($this->http->post("/inbox/{$itemId}/hide")));
    }

    public function unhide(string $itemId): InboxItem
    {
        return InboxItem::fromArray(self::unwrap($this->http->post("/inbox/{$itemId}/unhide")));
    }

    /** Delete the comment on the platform, including our own reply (which needs the `publish` scope). */
    public function delete(string $itemId): void
    {
        $this->http->delete("/inbox/{$itemId}");
    }

    /** Like, upvote or favourite the item, where canLike is true. Needs the `publish` scope. */
    public function like(string $itemId): InboxItem
    {
        return InboxItem::fromArray(self::unwrap($this->http->post("/inbox/{$itemId}/like")));
    }

    public function unlike(string $itemId): InboxItem
    {
        return InboxItem::fromArray(self::unwrap($this->http->post("/inbox/{$itemId}/unlike")));
    }

    /** Pin our own comment, where canPin is true. Needs the `publish` scope. */
    public function pin(string $itemId): InboxItem
    {
        return InboxItem::fromArray(self::unwrap($this->http->post("/inbox/{$itemId}/pin")));
    }

    public function unpin(string $itemId): InboxItem
    {
        return InboxItem::fromArray(self::unwrap($this->http->post("/inbox/{$itemId}/unpin")));
    }

    /** React to a message with an emoji, or null to remove ours. Needs the `publish` scope. */
    public function react(string $itemId, ?string $reaction): InboxItem
    {
        return InboxItem::fromArray(
            self::unwrap($this->http->post("/inbox/{$itemId}/react", ['reaction' => $reaction])),
        );
    }

    /**
     * Open a DM to $handle from $accountId, or privately answer the inbox comment $commentId.
     * Needs the `publish` scope.
     *
     * @param array<int, string>|null $mediaIds
     */
    public function startConversation(
        string $text,
        ?string $accountId = null,
        ?string $handle = null,
        ?string $commentId = null,
        ?array $mediaIds = null,
    ): InboxStartConversationResult {
        $body = self::compact([
            'account_id' => $accountId,
            'handle' => $handle,
            'comment_id' => $commentId,
            'text' => $text,
            'media_ids' => $mediaIds,
        ]);

        return InboxStartConversationResult::fromArray(
            self::unwrap($this->http->post('/inbox/conversations', $body)),
        );
    }

    /** Show or clear the typing indicator in a DM thread. Needs the `publish` scope. */
    public function setTyping(string $conversationId, string $accountId, bool $on = true): bool
    {
        $result = self::unwrap($this->http->post(
            "/inbox/conversations/{$conversationId}/typing",
            ['account_id' => $accountId, 'on' => $on],
        ));

        return is_array($result) && ($result['typing'] ?? false) === true;
    }

    /**
     * Replies an automation or the agent drafted that a person still has to send.
     *
     * @return array<int, InboxApproval>
     */
    public function listApprovals(?string $workspaceId = null): array
    {
        return InboxApproval::listFrom(
            self::unwrap($this->http->get('/inbox/approvals', ['workspace_id' => $workspaceId])),
        );
    }

    /** Send the draft, or $text in its place. */
    public function approveReply(int $approvalId, ?string $text = null): InboxApprovalDecision
    {
        $body = $text !== null ? ['text' => $text] : [];

        return InboxApprovalDecision::fromArray(
            self::unwrap($this->http->post("/inbox/approvals/{$approvalId}/approve", $body)),
        );
    }

    public function rejectReply(int $approvalId): InboxApprovalDecision
    {
        return InboxApprovalDecision::fromArray(
            self::unwrap($this->http->post("/inbox/approvals/{$approvalId}/reject")),
        );
    }
}
