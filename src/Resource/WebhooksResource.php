<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\Webhook;
use Fopost\Sdk\Model\WebhookDelivery;
use Fopost\Sdk\Undefined;

/**
 * $client->webhooks(): the endpoints FoPost sends events to, and their delivery log.
 *
 * Verify what arrives with `Fopost\Sdk\Webhooks::verify()`. Bodies are camelCase. Needs the `webhooks` scope.
 */
final class WebhooksResource extends Resource
{
    /** @return array<int, Webhook> */
    public function list(): array
    {
        return Webhook::listFrom(self::unwrap($this->http->get('/webhooks')));
    }

    /**
     * The signing secret comes back once, on `->secret`; omit `$secret` to have one generated.
     *
     * @param array<int, string> $events
     * @param array<string, string>|null $headers Sent with every delivery; values are never returned.
     */
    public function create(
        string $workspaceId,
        string $url,
        array $events,
        ?string $name = null,
        ?string $secret = null,
        ?array $headers = null,
    ): Webhook {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'url' => $url,
            'events' => array_values($events),
            'name' => $name,
            'secret' => $secret,
            'headers' => $headers,
        ]);

        return Webhook::fromArray(self::unwrap($this->http->post('/webhooks', $body)));
    }

    /**
     * Partial update: only the fields you pass are sent. Passing `$secret` rotates it and
     * returns the new value once. `$headers` replaces the header set; a null value keeps an
     * existing header's stored value, and null for the whole set clears every header.
     *
     * @param array<int, string>|Undefined $events
     * @param array<string, string|null>|null|Undefined $headers
     */
    public function update(
        string $webhookId,
        string|null|Undefined $name = Undefined::Value,
        string|Undefined $url = Undefined::Value,
        array|Undefined $events = Undefined::Value,
        bool|Undefined $active = Undefined::Value,
        string|Undefined $secret = Undefined::Value,
        array|null|Undefined $headers = Undefined::Value,
    ): Webhook {
        $body = [];
        if (!Undefined::is($name)) {
            $body['name'] = $name;
        }
        if (!Undefined::is($url)) {
            $body['url'] = $url;
        }
        if (!Undefined::is($events)) {
            /** @var array<int, string> $events */
            $body['events'] = array_values($events);
        }
        if (!Undefined::is($active)) {
            $body['active'] = $active;
        }
        if (!Undefined::is($secret)) {
            $body['secret'] = $secret;
        }
        if (!Undefined::is($headers)) {
            $body['headers'] = $headers;
        }

        return Webhook::fromArray(self::unwrap($this->http->put("/webhooks/{$webhookId}", $body)));
    }

    public function delete(string $webhookId): void
    {
        $this->http->delete("/webhooks/{$webhookId}");
    }

    /** Send a test event to the endpoint now. */
    public function test(string $webhookId): void
    {
        $this->http->post("/webhooks/{$webhookId}/test");
    }

    /**
     * Recent delivery attempts, newest first. `$status` is success or failed.
     *
     * @return array<int, WebhookDelivery>
     */
    public function listDeliveries(string $webhookId, ?string $status = null, ?int $limit = null): array
    {
        $query = self::compact(['status' => $status, 'limit' => $limit]);

        return WebhookDelivery::listFrom(self::unwrap($this->http->get("/webhooks/{$webhookId}/deliveries", $query)));
    }

    /** One attempt, with the body exactly as it was sent on `->payload`. */
    public function getDelivery(string $webhookId, string $deliveryId): WebhookDelivery
    {
        return WebhookDelivery::fromArray(
            self::unwrap($this->http->get("/webhooks/{$webhookId}/deliveries/{$deliveryId}")),
        );
    }

    /** Send that attempt's body again as a new attempt. A delivery already queued for retry answers 409. */
    public function redeliver(string $webhookId, string $deliveryId): void
    {
        $this->http->post("/webhooks/{$webhookId}/deliveries/{$deliveryId}/redeliver");
    }
}
