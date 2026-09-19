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

    /** Send the reply on the platform as the connected account. */
    public function reply(string $itemId, string $text): InboxReplyResult
    {
        return InboxReplyResult::fromArray(
            self::unwrap($this->http->post("/inbox/{$itemId}/reply", ['text' => $text])),
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

    /** Delete the comment on the platform. */
    public function delete(string $itemId): void
    {
        $this->http->delete("/inbox/{$itemId}");
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
