<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Model\ContactChannel;

final class ContactsTest extends TestCase
{
    /** @return array<string, mixed> */
    private function contactFixture(): array
    {
        return [
            'id' => 'con_1',
            'display_name' => 'Ada Okafor',
            'channels' => [
                ['platform' => 'instagram', 'handle' => 'adaokafor', 'externalId' => '178414'],
                ['platform' => 'x', 'handle' => 'ada_writes', 'externalId' => null],
            ],
            'source' => 'inbox',
            'note' => null,
            'first_seen_at' => '2026-04-02T09:14:00.000Z',
            'last_seen_at' => '2026-09-18T14:30:00.000Z',
            'fields' => ['plan_tier' => 'Pro'],
            'labels' => [['id' => 'lbl_1', 'name' => 'VIP', 'color' => '#0070f3']],
        ];
    }

    public function testListReadsTheContactsAndTheirPagination(): void
    {
        $this->transport->push(200, [
            'data' => [$this->contactFixture()],
            'pagination' => ['page' => 2, 'per_page' => 10, 'total' => 11],
        ]);

        $page = $this->client()->contacts()->list('w_1', 'ada', null, null, 2, 10);

        $this->assertStringContainsString('workspace_id=w_1', $this->transport->last()['url']);
        $this->assertStringContainsString('search=ada', $this->transport->last()['url']);
        $this->assertCount(1, $page);
        $this->assertSame('Ada Okafor', $page[0]->displayName);
        $this->assertSame('178414', $page[0]->channels[0]->externalId);
        $this->assertSame('Pro', $page[0]->fields['plan_tier']);
        $this->assertSame(11, $page->meta->total);
        $this->assertSame(2, $page->meta->currentPage);
    }

    public function testCreateSendsTheChannelsAsPlainArrays(): void
    {
        $this->transport->push(200, ['data' => $this->contactFixture()]);

        $this->client()->contacts()->create(
            'w_1',
            [ContactChannel::make('x', 'ada_writes')],
            'Ada Okafor',
            null,
            ['plan_tier' => 'Pro'],
        );

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/contacts', $this->transport->last()['url']);
        $this->assertSame([
            'workspace_id' => 'w_1',
            'channels' => [['platform' => 'x', 'handle' => 'ada_writes']],
            'display_name' => 'Ada Okafor',
            'fields' => ['plan_tier' => 'Pro'],
        ], $this->transport->lastJson());
    }

    public function testUpdateSendsOnlyWhatWasPassedAndKeepsANullField(): void
    {
        $this->transport->push(200, ['data' => $this->contactFixture()]);

        $this->client()->contacts()->update('con_1', fields: ['region' => null]);

        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertSame(['fields' => ['region' => null]], $this->transport->lastJson());
    }

    public function testConversationsReadsTheThreadsAContactAppearsIn(): void
    {
        $this->transport->push(200, ['data' => [[
            'key' => 't_182736',
            'account_id' => 'acc_1',
            'account_username' => 'yourbrand',
            'platform' => 'instagram',
            'messages' => 14,
            'received' => 9,
            'sent' => 5,
            'last_message_at' => '2026-09-18T14:30:00.000Z',
            'last_item_id' => 'inb_1',
        ]]]);

        $rows = $this->client()->contacts()->conversations('con_1', 10);

        $this->assertStringContainsString('/contacts/con_1/conversations?limit=10', $this->transport->last()['url']);
        $this->assertCount(1, $rows);
        $this->assertSame('t_182736', $rows[0]->key);
        $this->assertSame(9, $rows[0]->received);
    }

    public function testImportReportsWhatMergedAndWhatWasSkipped(): void
    {
        $this->transport->push(200, ['data' => [
            'created' => 1,
            'merged' => 2,
            'skipped' => [['row' => 4, 'reason' => 'platform and handle are both required']],
            'unknownColumns' => ['lifetime_value'],
        ]]);

        $result = $this->client()->contacts()->import('w_1', "platform,handle\nx,ada_writes");

        $this->assertSame(1, $result->created);
        $this->assertSame(2, $result->merged);
        $this->assertSame(['lifetime_value'], $result->unknownColumns);
        $this->assertSame(4, $result->skipped[0]['row']);
    }

    public function testCreateFieldPutsTheWorkspaceOnTheQuery(): void
    {
        $this->transport->push(200, ['data' => [
            'id' => 'fld_1',
            'key' => 'plan_tier',
            'name' => 'Plan Tier',
            'type' => 'select',
            'options' => ['Free', 'Pro'],
            'position' => 0,
        ]]);

        $field = $this->client()->contacts()->createField('w_1', 'plan_tier', 'Plan Tier', 'select', ['Free', 'Pro']);

        $this->assertStringContainsString('/contacts/fields?workspace_id=w_1', $this->transport->last()['url']);
        $this->assertSame('plan_tier', $field->key);
        $this->assertSame(['Free', 'Pro'], $field->options);
    }

    public function testConversationAnalyticsReadsTheAnalyticsRoute(): void
    {
        $this->transport->push(200, ['data' => [
            'conversations' => [[
                'key' => 't_1',
                'accountId' => 'acc_1',
                'platform' => 'instagram',
                'received' => 9,
                'sent' => 5,
                'answered' => 5,
                'open' => 1,
                'medianResponseMinutes' => 47,
                'firstMessageAt' => null,
                'lastMessageAt' => null,
            ]],
            'total' => 128,
            'page' => 1,
            'perPage' => 25,
        ]]);

        $report = $this->client()->contacts()->conversationAnalytics(days: 30, sort: 'slowest');

        $this->assertStringContainsString('/analytics/inbox/conversations?', $this->transport->last()['url']);
        $this->assertStringContainsString('days=30', $this->transport->last()['url']);
        $this->assertStringContainsString('sort=slowest', $this->transport->last()['url']);
        $this->assertSame(128, $report->total);
        $this->assertSame(47, $report->conversations[0]->medianResponseMinutes);
    }
}
