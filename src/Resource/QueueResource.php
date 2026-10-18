<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use DateTimeImmutable;
use Fopost\Sdk\Model\QueueSlot;
use Fopost\Sdk\Model\QueueSlotDeletion;
use Fopost\Sdk\Model\QueueSlotUpdate;
use Fopost\Sdk\Undefined;

/**
 * $client->queue(): the weekly posting slots a queued post lands in.
 *
 * Create a post with `schedule: 'queue'` to take the next free slot. Needs the `posts` scope.
 */
final class QueueResource extends Resource
{
    /** @return array<int, QueueSlot> */
    public function listSlots(string $workspaceId): array
    {
        return QueueSlot::listFrom(self::unwrap($this->http->get('/queue/slots', ['workspace_id' => $workspaceId])));
    }

    /** `$weekday` is 0 = Sunday … 6 = Saturday; `$time` is HH:MM in `$timezone`; no account means every account. */
    public function createSlot(
        string $workspaceId,
        int $weekday,
        string $time,
        string $timezone,
        ?string $accountId = null,
    ): QueueSlot {
        $body = self::compact([
            'workspace_id' => $workspaceId,
            'weekday' => $weekday,
            'time' => $time,
            'timezone' => $timezone,
            'account_id' => $accountId,
        ]);

        return QueueSlot::fromArray(self::unwrap($this->http->post('/queue/slots', $body)));
    }

    /** Partial update: only the fields you pass are sent. Posts queued on the slot move with it. */
    public function updateSlot(
        string $slotId,
        int|Undefined $weekday = Undefined::Value,
        string|Undefined $time = Undefined::Value,
        string|Undefined $timezone = Undefined::Value,
        string|null|Undefined $accountId = Undefined::Value,
    ): QueueSlotUpdate {
        $body = [];
        if (!Undefined::is($weekday)) {
            $body['weekday'] = $weekday;
        }
        if (!Undefined::is($time)) {
            $body['time'] = $time;
        }
        if (!Undefined::is($timezone)) {
            $body['timezone'] = $timezone;
        }
        if (!Undefined::is($accountId)) {
            $body['account_id'] = $accountId;
        }

        return QueueSlotUpdate::fromArray(self::unwrap($this->http->put("/queue/slots/{$slotId}", $body)));
    }

    public function deleteSlot(string $slotId): QueueSlotDeletion
    {
        return QueueSlotDeletion::fromArray(self::unwrap($this->http->delete("/queue/slots/{$slotId}")));
    }

    /** When the next queued post would go out; null when no slot applies. */
    public function next(string $workspaceId, ?string $accountId = null): ?DateTimeImmutable
    {
        $query = self::compact(['workspace_id' => $workspaceId, 'account_id' => $accountId]);
        $body = self::asArray(self::unwrap($this->http->get('/queue/next', $query)));
        $next = $body['next'] ?? null;

        return is_string($next) && $next !== '' ? new DateTimeImmutable($next) : null;
    }

    /**
     * The next `$count` times the queue would fill, oldest first.
     *
     * @return array<int, DateTimeImmutable>
     */
    public function preview(string $workspaceId, ?string $accountId = null, ?int $count = null): array
    {
        $query = self::compact(['workspace_id' => $workspaceId, 'account_id' => $accountId, 'count' => $count]);
        $times = self::unwrap($this->http->get('/queue/preview', $query));

        $out = [];
        foreach (is_array($times) ? $times : [] as $time) {
            if (is_string($time) && $time !== '') {
                $out[] = new DateTimeImmutable($time);
            }
        }

        return $out;
    }
}
