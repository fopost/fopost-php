<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class QueueTest extends TestCase
{
    public function testSlotsAreListedCreatedUpdatedAndDeleted(): void
    {
        $slot = [
            'id' => 'qs_1',
            'workspace_id' => 'w_1',
            'account_id' => null,
            'weekday' => 1,
            'time' => '09:00',
            'timezone' => 'Europe/Zagreb',
            'created_at' => '2026-03-01T09:00:00Z',
        ];
        $this->transport->push(200, ['data' => [$slot]]);
        $this->transport->push(201, $slot);
        $this->transport->push(200, $slot + ['moved' => [[
            'id' => 'p_1',
            'from' => '2026-03-02T09:00:00Z',
            'to' => '2026-03-02T10:00:00Z',
        ]]]);
        $this->transport->push(200, ['deleted' => true, 'moved' => []]);

        $queue = $this->client()->queue();
        $slots = $queue->listSlots('w_1');
        $created = $queue->createSlot('w_1', 1, '09:00', 'Europe/Zagreb');
        $updated = $queue->updateSlot('qs_1', time: '10:00');
        $deleted = $queue->deleteSlot('qs_1');

        $this->assertSame(
            'https://api.fopost.com/v1/queue/slots?workspace_id=w_1',
            $this->transport->requests[0]['url'],
        );
        $this->assertSame(1, $slots[0]->weekday);
        $this->assertNull($slots[0]->accountId);

        $this->assertSame('POST', $this->transport->requests[1]['method']);
        $this->assertSame('https://api.fopost.com/v1/queue/slots', $this->transport->requests[1]['url']);
        $this->assertSame(
            ['workspace_id' => 'w_1', 'weekday' => 1, 'time' => '09:00', 'timezone' => 'Europe/Zagreb'],
            json_decode((string) $this->transport->requests[1]['body'], true),
        );
        $this->assertSame('qs_1', $created->id);

        $this->assertSame('PUT', $this->transport->requests[2]['method']);
        $this->assertSame('https://api.fopost.com/v1/queue/slots/qs_1', $this->transport->requests[2]['url']);
        $this->assertSame(['time' => '10:00'], json_decode((string) $this->transport->requests[2]['body'], true));
        $this->assertSame('p_1', $updated->moved[0]->id);
        $this->assertSame('2026-03-02T10:00:00+00:00', $updated->moved[0]->to?->format(DATE_ATOM));

        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/queue/slots/qs_1', $this->transport->last()['url']);
        $this->assertTrue($deleted->deleted);
    }

    public function testNextAndPreviewReadTheUpcomingTimes(): void
    {
        $this->transport->push(200, ['next' => '2026-03-02T09:00:00Z']);
        $this->transport->push(200, ['data' => ['2026-03-02T09:00:00Z', '2026-03-03T09:00:00Z']]);
        $this->transport->push(200, ['next' => null]);

        $next = $this->client()->queue()->next('w_1', 'a_1');
        $preview = $this->client()->queue()->preview('w_1', count: 2);
        $none = $this->client()->queue()->next('w_1');

        $this->assertSame(
            'https://api.fopost.com/v1/queue/next?workspace_id=w_1&account_id=a_1',
            $this->transport->requests[0]['url'],
        );
        $this->assertSame('2026-03-02', $next?->format('Y-m-d'));
        $this->assertSame(
            'https://api.fopost.com/v1/queue/preview?workspace_id=w_1&count=2',
            $this->transport->requests[1]['url'],
        );
        $this->assertCount(2, $preview);
        $this->assertSame('2026-03-03', $preview[1]->format('Y-m-d'));
        $this->assertNull($none);
    }
}
