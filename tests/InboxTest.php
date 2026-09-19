<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use DateTimeImmutable;

final class InboxTest extends TestCase
{
    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private static function item(array $overrides = []): array
    {
        return $overrides + ['id' => 'i_1', 'platform' => 'x', 'type' => 'comment', 'state' => 'read'];
    }

    public function testListSendsSnakeCaseFiltersAndParsesThePage(): void
    {
        $this->transport->push(200, [
            'data' => [[
                'id' => 'i_1',
                'workspaceId' => 'w_1',
                'platform' => 'instagram',
                'type' => 'comment',
                'state' => 'unread',
                'direction' => 'inbound',
                'authorName' => 'Jordan Rivera',
                'authorHandle' => 'jordan',
                'text' => 'Love this',
                'attachments' => [['kind' => 'image', 'url' => '/v1/inbox/i_1/attachments/0', 'width' => 640]],
                'platformCreatedAt' => '2026-09-18T10:00:00Z',
                'canReply' => true,
                'hidden' => false,
                'account' => ['id' => 'acc_1', 'platform' => 'instagram', 'username' => 'yourbrand'],
            ]],
            'meta' => ['page' => 2, 'perPage' => 10, 'total' => 11],
        ]);

        $page = $this->client()->inbox()->list(
            workspaceId: 'w_1',
            type: 'comment',
            state: 'unread',
            page: 2,
            perPage: 10,
        );

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/inbox?workspace_id=w_1&type=comment&state=unread&page=2&per_page=10',
            $this->transport->last()['url'],
        );
        $this->assertCount(1, $page);
        $this->assertSame(2, $page->meta->currentPage);
        $this->assertSame(10, $page->meta->perPage);
        $this->assertSame(11, $page->meta->total);

        $item = $page[0];
        $this->assertSame('i_1', $item->id);
        $this->assertSame('comment', $item->type);
        $this->assertSame('Jordan Rivera', $item->authorName);
        $this->assertSame('image', $item->attachments[0]->kind);
        $this->assertSame(640, $item->attachments[0]->width);
        $this->assertSame('2026-09-18', $item->platformCreatedAt?->format('Y-m-d'));
        $this->assertTrue($item->canReply);
        $this->assertSame('yourbrand', $item->account['username'] ?? null);
    }

    public function testThreadsAndConversationsHitTheirOwnPaths(): void
    {
        $this->transport->push(200, ['data' => [[
            'accountId' => 'acc_1',
            'postExternalId' => 'ext_9',
            'commentCount' => 4,
            'unreadCount' => 1,
            'lastCommentText' => 'Any plans for dark mode?',
        ]], 'meta' => ['page' => 1, 'perPage' => 25, 'total' => 1]]);

        $threads = $this->client()->inbox()->threads(workspaceId: 'w_1', kind: 'mentions');

        $this->assertSame(
            'https://api.fopost.com/v1/inbox/posts?workspace_id=w_1&kind=mentions&page=1&per_page=25',
            $this->transport->last()['url'],
        );
        $this->assertSame(4, $threads[0]->commentCount);
        $this->assertSame('ext_9', $threads[0]->postExternalId);

        $this->transport->push(200, ['data' => [[
            'accountId' => 'acc_1',
            'conversationId' => 'c_1',
            'messageCount' => 3,
            'unreadCount' => 0,
            'lastMessageOutbound' => true,
            'participant' => ['name' => 'Sam Okafor', 'handle' => 'sam'],
        ]], 'meta' => ['page' => 1, 'perPage' => 25, 'total' => 1]]);

        $conversations = $this->client()->inbox()->conversations(workspaceId: 'w_1', platform: 'x');

        $this->assertSame(
            'https://api.fopost.com/v1/inbox/conversations?workspace_id=w_1&platform=x&page=1&per_page=25',
            $this->transport->last()['url'],
        );
        $this->assertSame('c_1', $conversations[0]->conversationId);
        $this->assertTrue($conversations[0]->lastMessageOutbound);
        $this->assertSame('sam', $conversations[0]->participant['handle'] ?? null);
    }

    public function testUnreadCountAccountsAndPlatformsAreRead(): void
    {
        $this->transport->push(200, ['count' => 7]);
        $this->assertSame(7, $this->client()->inbox()->unreadCount('w_1'));
        $this->assertSame(
            'https://api.fopost.com/v1/inbox/unread-count?workspace_id=w_1',
            $this->transport->last()['url'],
        );

        $this->transport->push(200, ['data' => [[
            'id' => 'acc_1',
            'platform' => 'facebook',
            'username' => 'yourbrand',
            'name' => 'Your Brand',
            'inboxSupported' => true,
            'dmSupported' => false,
            'dmPendingReason' => 'pending_review',
        ]]]);
        $accounts = $this->client()->inbox()->accounts('w_1');
        $this->assertSame('https://api.fopost.com/v1/inbox/accounts?workspace_id=w_1', $this->transport->last()['url']);
        $this->assertTrue($accounts[0]->inboxSupported);
        $this->assertFalse($accounts[0]->dmSupported);
        $this->assertSame('pending_review', $accounts[0]->dmPendingReason);

        $this->transport->push(200, ['data' => [['platform' => 'threads', 'comments' => 'live', 'dms' => 'none']]]);
        $platforms = $this->client()->inbox()->platforms();
        $this->assertSame('https://api.fopost.com/v1/inbox/platforms', $this->transport->last()['url']);
        $this->assertSame('live', $platforms[0]->comments);
    }

    public function testMarkThreadReadAndRefreshPostSnakeCaseBodies(): void
    {
        $this->transport->push(200, ['data' => ['updated' => 3]]);

        $updated = $this->client()->inbox()->markThreadRead('w_1', 'acc_1', postExternalId: 'ext_9');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/inbox/read', $this->transport->last()['url']);
        $this->assertSame(
            ['workspace_id' => 'w_1', 'account_id' => 'acc_1', 'post_external_id' => 'ext_9'],
            $this->transport->lastJson(),
        );
        $this->assertSame(3, $updated);

        $this->transport->push(200, ['data' => [
            'accountsPolled' => 2,
            'newItems' => 5,
            'rateLimited' => 0,
            'dmReconnect' => [['platform' => 'x', 'account' => 'yourbrand']],
        ]]);

        $result = $this->client()->inbox()->refresh('w_1');

        $this->assertSame('https://api.fopost.com/v1/inbox/refresh', $this->transport->last()['url']);
        $this->assertSame(['workspace_id' => 'w_1'], $this->transport->lastJson());
        $this->assertSame(2, $result->accountsPolled);
        $this->assertSame(5, $result->newItems);
        $this->assertSame('x', $result->dmReconnect[0]['platform']);
    }

    public function testUpdatePatchesStateWithACamelCaseSnoozedUntil(): void
    {
        $this->transport->push(200, ['data' => [
            'id' => 'i_1',
            'platform' => 'x',
            'type' => 'mention',
            'state' => 'snoozed',
            'snoozedUntil' => '2026-09-20T09:00:00Z',
        ]]);

        $item = $this->client()->inbox()->update('i_1', 'snoozed', new DateTimeImmutable('2026-09-20T09:00:00Z'));

        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/inbox/i_1', $this->transport->last()['url']);
        $this->assertSame(
            ['state' => 'snoozed', 'snoozedUntil' => '2026-09-20T09:00:00Z'],
            $this->transport->lastJson(),
        );
        $this->assertSame('snoozed', $item->state);
        $this->assertSame('2026-09-20', $item->snoozedUntil?->format('Y-m-d'));

        $this->transport->push(200, ['data' => self::item(['type' => 'mention'])]);
        $this->client()->inbox()->update('i_1', 'read');
        $this->assertSame(['state' => 'read'], $this->transport->lastJson());
    }

    public function testReplyHideUnhideAndDelete(): void
    {
        $this->transport->push(200, ['data' => [
            'item' => self::item(),
            'reply' => ['externalId' => 'r_1', 'externalUrl' => 'https://x.com/yourbrand/status/1'],
        ]]);

        $result = $this->client()->inbox()->reply('i_1', 'Thanks for the kind words');

        $this->assertSame('https://api.fopost.com/v1/inbox/i_1/reply', $this->transport->last()['url']);
        $this->assertSame(['text' => 'Thanks for the kind words'], $this->transport->lastJson());
        $this->assertSame('i_1', $result->item->id);
        $this->assertSame('r_1', $result->externalId);
        $this->assertSame('https://x.com/yourbrand/status/1', $result->externalUrl);

        $this->transport->push(200, ['data' => self::item(['hidden' => true])]);
        $this->assertTrue($this->client()->inbox()->hide('i_1')->hidden);
        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/inbox/i_1/hide', $this->transport->last()['url']);

        $this->transport->push(200, ['data' => self::item(['hidden' => false])]);
        $this->assertFalse($this->client()->inbox()->unhide('i_1')->hidden);
        $this->assertSame('https://api.fopost.com/v1/inbox/i_1/unhide', $this->transport->last()['url']);

        $this->transport->push(200, ['data' => ['deleted' => true]]);
        $this->client()->inbox()->delete('i_1');
        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/inbox/i_1', $this->transport->last()['url']);
    }

    public function testApprovalsUseIntegerIdsAndAnOptionalText(): void
    {
        $this->transport->push(200, ['data' => [[
            'id' => 42,
            'workspaceId' => 'w_1',
            'source' => 'automation',
            'reply' => 'Glad you like it',
            'createdAt' => '2026-09-18T10:00:00Z',
            'item' => ['id' => 'i_1', 'platform' => 'x', 'type' => 'comment', 'state' => 'unread'],
        ]]]);

        $approvals = $this->client()->inbox()->listApprovals('w_1');

        $this->assertSame(
            'https://api.fopost.com/v1/inbox/approvals?workspace_id=w_1',
            $this->transport->last()['url'],
        );
        $this->assertSame(42, $approvals[0]->id);
        $this->assertSame('Glad you like it', $approvals[0]->reply);
        $this->assertSame('i_1', $approvals[0]->item['id'] ?? null);

        $this->transport->push(200, ['data' => ['id' => 42, 'outcome' => 'sent']]);
        $decision = $this->client()->inbox()->approveReply(42, 'Glad you like it, more soon');
        $this->assertSame('https://api.fopost.com/v1/inbox/approvals/42/approve', $this->transport->last()['url']);
        $this->assertSame(['text' => 'Glad you like it, more soon'], $this->transport->lastJson());
        $this->assertSame('sent', $decision->outcome);

        $this->transport->push(200, ['data' => ['id' => 42, 'outcome' => 'sent']]);
        $this->client()->inbox()->approveReply(42);
        $this->assertSame([], $this->transport->lastJson());

        $this->transport->push(200, ['data' => ['id' => 42, 'outcome' => 'rejected']]);
        $decision = $this->client()->inbox()->rejectReply(42);
        $this->assertSame('https://api.fopost.com/v1/inbox/approvals/42/reject', $this->transport->last()['url']);
        $this->assertSame('rejected', $decision->outcome);
    }
}
