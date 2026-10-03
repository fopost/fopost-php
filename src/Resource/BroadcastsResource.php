<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\Broadcast;
use Fopost\Sdk\Model\BroadcastRecipient;
use Fopost\Sdk\Model\Page;
use Fopost\Sdk\Model\PageMeta;
use Fopost\Sdk\Undefined;

/**
 * $client->broadcasts(): one message into every conversation the workspace
 * already has with a segment of its contacts.
 *
 * Nothing is sent into a closed messaging window. Messenger and Instagram
 * take a business-initiated message only within 24 hours of the contact's
 * last one, so recipients outside it come back skipped with window_closed
 * rather than attempted — which is why the number sent is often lower than
 * the audience. Telegram, Slack, Bluesky and Reddit have no window.
 */
final class BroadcastsResource extends Resource
{
    /**
     * One page of broadcasts, newest first.
     *
     * Omit $workspaceId to span every workspace the key can reach; each
     * broadcast then carries workspaceId.
     *
     * @return Page<Broadcast>
     */
    public function list(
        ?string $workspaceId = null,
        ?string $status = null,
        int $page = 1,
        int $perPage = 25,
    ): Page {
        $body = self::asArray($this->http->get('/broadcasts', self::compact([
            'workspace_id' => $workspaceId,
            'status' => $status,
            'page' => $page,
            'per_page' => $perPage,
        ])));

        $items = Broadcast::listFrom($body['data'] ?? []);
        $meta = isset($body['pagination']) && is_array($body['pagination'])
            ? PageMeta::fromArray($body['pagination'])
            : PageMeta::empty();

        return new Page($items, $meta);
    }

    public function get(string $broadcastId): Broadcast
    {
        return Broadcast::fromArray(self::unwrap($this->http->get("/broadcasts/{$broadcastId}")));
    }

    /**
     * Create it without sending.
     *
     * Give $scheduledAt to have it go out on its own at that time; otherwise
     * call send(). An omitted $audience means every contact in the workspace.
     *
     * @param array<string, mixed>|null $audience
     */
    public function create(
        string $workspaceId,
        string $accountId,
        string $name,
        string $text,
        ?string $mediaId = null,
        ?array $audience = null,
        ?string $scheduledAt = null,
    ): Broadcast {
        $body = self::compact([
            'workspace_id' => $workspaceId,
            'account_id' => $accountId,
            'name' => $name,
            'text' => $text,
            'media_id' => $mediaId,
            'audience' => $audience,
            'scheduled_at' => $scheduledAt,
        ]);

        return Broadcast::fromArray(self::unwrap($this->http->post('/broadcasts', $body)));
    }

    /**
     * Partial update: only the fields you pass are sent. Only a draft or
     * scheduled broadcast can be edited.
     *
     * @param array<string, mixed>|Undefined $audience
     */
    public function update(
        string $broadcastId,
        string|Undefined $name = Undefined::Value,
        string|Undefined $text = Undefined::Value,
        string|null|Undefined $mediaId = Undefined::Value,
        array|Undefined $audience = Undefined::Value,
        string|null|Undefined $scheduledAt = Undefined::Value,
    ): Broadcast {
        $body = [];
        if (!Undefined::is($name)) {
            $body['name'] = $name;
        }
        if (!Undefined::is($text)) {
            $body['text'] = $text;
        }
        if (!Undefined::is($mediaId)) {
            $body['media_id'] = $mediaId;
        }
        if (!Undefined::is($audience)) {
            $body['audience'] = $audience;
        }
        if (!Undefined::is($scheduledAt)) {
            $body['scheduled_at'] = $scheduledAt;
        }

        return Broadcast::fromArray(
            self::unwrap($this->http->request('PATCH', "/broadcasts/{$broadcastId}", $body)),
        );
    }

    /**
     * Freeze the audience into a recipient list and start sending.
     *
     * The returned recipients count is how many contacts matched, not how
     * many will be messaged — the messaging window decides that. Needs the
     * publish scope as well as inbox.
     *
     * @return array<string, mixed>
     */
    public function send(string $broadcastId): array
    {
        return self::asArray(self::unwrap($this->http->post("/broadcasts/{$broadcastId}/send", [])));
    }

    /**
     * Stop it where it stands. Anyone not yet written to stays unsent;
     * messages already delivered are not recalled. Needs the publish scope.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $broadcastId): array
    {
        return self::asArray(
            self::unwrap($this->http->post("/broadcasts/{$broadcastId}/cancel", [])),
        );
    }

    /**
     * One row per contact, with what became of their message. A skipped row
     * carries skipReason.
     *
     * @return Page<BroadcastRecipient>
     */
    public function recipients(
        string $broadcastId,
        ?string $status = null,
        int $page = 1,
        int $perPage = 50,
    ): Page {
        $body = self::asArray($this->http->get("/broadcasts/{$broadcastId}/recipients", self::compact([
            'status' => $status,
            'page' => $page,
            'per_page' => $perPage,
        ])));

        $items = BroadcastRecipient::listFrom($body['data'] ?? []);
        $meta = isset($body['pagination']) && is_array($body['pagination'])
            ? PageMeta::fromArray($body['pagination'])
            : PageMeta::empty();

        return new Page($items, $meta);
    }

    /**
     * Removes the broadcast and its recipient records. Messages already sent
     * stay in the conversations they went to.
     */
    public function delete(string $broadcastId): void
    {
        $this->http->delete("/broadcasts/{$broadcastId}");
    }
}
