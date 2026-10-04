<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Model\SequenceStep;

final class BroadcastsTest extends TestCase
{
    /** @return array<string, mixed> */
    private function broadcastFixture(): array
    {
        return [
            'id' => 'bc_1',
            'name' => 'September check-in',
            'text' => 'New colours just landed.',
            'account_id' => 'acc_1',
            'audience' => ['platforms' => ['instagram']],
            'status' => 'sent',
            'scheduled_at' => null,
            'sent_at' => '2026-09-19T10:04:00.000Z',
            'created_at' => '2026-09-19T09:58:00.000Z',
            'counts' => ['total' => 3, 'sent' => 2, 'skipped' => 1, 'failed' => 0, 'pending' => 0],
        ];
    }

    public function testListReadsTheBroadcastsAndTheirPagination(): void
    {
        $this->transport->push(200, [
            'data' => [$this->broadcastFixture()],
            'pagination' => ['page' => 2, 'per_page' => 10, 'total' => 11],
        ]);

        $page = $this->client()->broadcasts()->list('w_1', 'sent', 2, 10);

        $this->assertStringContainsString('workspace_id=w_1', $this->transport->last()['url']);
        $this->assertStringContainsString('status=sent', $this->transport->last()['url']);
        $this->assertCount(1, $page);
        $this->assertSame('September check-in', $page[0]->name);
        $this->assertNotNull($page[0]->counts);
        $this->assertSame(2, $page[0]->counts->sent);
        $this->assertSame(1, $page[0]->counts->skipped);
        $this->assertSame(11, $page->meta->total);
    }

    public function testCreateSendsTheSnakeCaseBody(): void
    {
        $this->transport->push(201, ['data' => $this->broadcastFixture()]);

        $this->client()->broadcasts()->create(
            'w_1',
            'acc_1',
            'September check-in',
            'New colours just landed.',
            null,
            ['platforms' => ['instagram']],
            '2026-10-01T09:00:00.000Z',
        );

        $body = $this->transport->lastJson();
        $this->assertSame('w_1', $body['workspace_id']);
        $this->assertSame('acc_1', $body['account_id']);
        $this->assertSame('2026-10-01T09:00:00.000Z', $body['scheduled_at']);
        $this->assertSame(['platforms' => ['instagram']], $body['audience']);
    }

    /** A closed messaging window has to be readable, or a non-send is a mystery. */
    public function testASkippedRecipientKeepsItsReason(): void
    {
        $this->transport->push(200, [
            'data' => [[
                'contact_id' => 'con_1',
                'display_name' => 'Sam Rivera',
                'status' => 'skipped',
                'skip_reason' => 'window_closed',
                'sent_at' => null,
                'error' => null,
            ]],
            'pagination' => ['page' => 1, 'per_page' => 50, 'total' => 1],
        ]);

        $page = $this->client()->broadcasts()->recipients('bc_1', 'skipped');

        $this->assertStringContainsString('status=skipped', $this->transport->last()['url']);
        $this->assertSame('skipped', $page[0]->status);
        $this->assertSame('window_closed', $page[0]->skipReason);
    }

    public function testSendAndCancelPostToTheirOwnPaths(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'bc_1', 'status' => 'sending', 'recipients' => 3]]);
        $result = $this->client()->broadcasts()->send('bc_1');

        $this->assertStringContainsString('/broadcasts/bc_1/send', $this->transport->last()['url']);
        $this->assertSame(3, $result['recipients']);

        $this->transport->push(200, ['data' => ['id' => 'bc_1', 'status' => 'cancelled']]);
        $cancelled = $this->client()->broadcasts()->cancel('bc_1');

        $this->assertStringContainsString('/broadcasts/bc_1/cancel', $this->transport->last()['url']);
        $this->assertSame('cancelled', $cancelled['status']);
    }

    public function testSequenceStepsTravelAsPlainArrays(): void
    {
        $this->transport->push(201, ['data' => [
            'id' => 'seq_1',
            'name' => 'Welcome',
            'account_id' => 'acc_1',
            'steps' => [['delay_hours' => 0, 'text' => 'Hi'], ['delay_hours' => 48, 'text' => 'Still here?']],
            'status' => 'active',
            'created_at' => '2026-09-12T08:00:00.000Z',
        ]]);

        $sequence = $this->client()->sequences()->create('w_1', 'acc_1', 'Welcome', [
            SequenceStep::make(0, 'Hi'),
        ]);

        $this->assertSame(48.0, $sequence->steps[1]->delayHours);
        // JSON has one number type, so 0.0 arrives back as 0; the shape is what matters.
        $this->assertEquals(
            [['delay_hours' => 0, 'text' => 'Hi']],
            $this->transport->lastJson()['steps'],
        );
    }

    public function testEnrollTakesIdsOrAnAudience(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'seq_1', 'enrolled' => 2]]);
        $this->client()->sequences()->enroll('seq_1', ['con_1', 'con_2']);
        $this->assertSame(['con_1', 'con_2'], $this->transport->lastJson()['contact_ids']);

        $this->transport->push(200, ['data' => ['id' => 'seq_1', 'enrolled' => 5]]);
        $this->client()->sequences()->enroll('seq_1', null, ['platforms' => ['telegram']]);
        $this->assertSame(['platforms' => ['telegram']], $this->transport->lastJson()['audience']);
    }

    public function testUnenrollNamesTheContactsItStops(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'seq_1', 'stopped' => 1]]);

        $result = $this->client()->sequences()->unenroll('seq_1', ['con_1']);

        $this->assertStringContainsString('/sequences/seq_1/unenroll', $this->transport->last()['url']);
        $this->assertSame(['con_1'], $this->transport->lastJson()['contact_ids']);
        $this->assertSame(1, $result['stopped']);
    }
}
