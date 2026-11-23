<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\GoogleAdScheduleSlot;
use Fopost\Sdk\Model\GoogleBidStrategy;
use Fopost\Sdk\Model\GoogleConversionAction;
use Fopost\Sdk\Model\GoogleLocalServicesLead;

/**
 * $client->ads()->google(): the Google Ads surface no other network has.
 *
 * Audiences and insights are on the ads resource itself and dispatch by connection. What is here
 * (bid strategies, the ad schedule, Local Services leads, conversions and raw GAQL) is Google only,
 * and a connection on another network answers 400.
 *
 * Every call needs the `ads` scope; anything that changes what a live account serves or bids also
 * needs `publish`. $customerId is digits only and has to name an account the connection's grant
 * reaches: any other answers 404.
 */
final class GoogleAdsResource extends Resource
{
    // ── Bid strategies and ad schedule ──

    /** @return array<int, GoogleBidStrategy> */
    public function bidStrategies(
        string $connectionId,
        string $customerId,
        ?string $workspaceId = null,
    ): array {
        return GoogleBidStrategy::listFrom(self::unwrap($this->http->get(
            '/ads/google/bid-strategies',
            self::params($connectionId, $customerId, $workspaceId),
        )));
    }

    /** Needs the `publish` scope as well as `ads`. */
    public function createBidStrategy(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        string $name,
        string $type,
        ?int $targetMinor = null,
    ): string {
        $body = self::scope($workspaceId, $connectionId, $customerId) + self::compact([
            'name' => $name,
            'type' => $type,
            'targetMinor' => $targetMinor,
        ]);

        return self::id($this->http->post('/ads/google/bid-strategies', $body));
    }

    /** @return array<int, GoogleAdScheduleSlot> */
    public function adSchedule(
        string $connectionId,
        string $customerId,
        string $campaignId,
        ?string $workspaceId = null,
    ): array {
        return GoogleAdScheduleSlot::listFrom(self::unwrap($this->http->get(
            '/ads/google/ad-schedule',
            self::params($connectionId, $customerId, $workspaceId, ['campaign_id' => $campaignId]),
        )));
    }

    /**
     * Replaces every slot on the campaign: Google has no partial edit for a schedule.
     * Needs the `publish` scope as well as `ads`.
     *
     * @param array<int, array<string, mixed>> $slots
     */
    public function setAdSchedule(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        string $campaignId,
        array $slots,
    ): int {
        $body = self::scope($workspaceId, $connectionId, $customerId) + [
            'campaignId' => $campaignId,
            'slots' => $slots,
        ];
        $result = self::unwrap($this->http->request('PUT', '/ads/google/ad-schedule', $body));

        return is_array($result) && is_int($result['slots'] ?? null) ? $result['slots'] : 0;
    }

    // ── Local Services leads ──

    /**
     * Read live on every call and never stored by FoPost.
     *
     * @return array<int, GoogleLocalServicesLead>
     */
    public function localServicesLeads(
        string $connectionId,
        string $customerId,
        string $since,
        string $until,
        ?string $workspaceId = null,
    ): array {
        return GoogleLocalServicesLead::listFrom(self::unwrap($this->http->get(
            '/ads/google/local-services',
            self::params($connectionId, $customerId, $workspaceId, ['since' => $since, 'until' => $until]),
        )));
    }

    // ── Conversions ──

    /** @return array<int, GoogleConversionAction> */
    public function conversionActions(
        string $connectionId,
        string $customerId,
        ?string $workspaceId = null,
    ): array {
        return GoogleConversionAction::listFrom(self::unwrap($this->http->get(
            '/ads/google/conversions',
            self::params($connectionId, $customerId, $workspaceId),
        )));
    }

    /** Needs the `publish` scope as well as `ads`. */
    public function createConversionAction(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        string $name,
        string $category,
        ?int $valueMinor = null,
        ?string $countingType = null,
    ): string {
        $body = self::scope($workspaceId, $connectionId, $customerId) + self::compact([
            'name' => $name,
            'category' => $category,
            'valueMinor' => $valueMinor,
            'countingType' => $countingType,
        ]);

        return self::id($this->http->post('/ads/google/conversions', $body));
    }

    /**
     * Offline conversions, matched to a click. Needs `publish` as well as `ads`.
     *
     * @param array<int, array<string, mixed>> $conversions
     */
    public function uploadConversions(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        array $conversions,
    ): int {
        $body = self::scope($workspaceId, $connectionId, $customerId) + ['conversions' => $conversions];

        return self::uploaded($this->http->post('/ads/google/conversions/upload', $body));
    }

    /**
     * Needs the `publish` scope as well as `ads`.
     *
     * @param array<int, array<string, mixed>> $adjustments
     */
    public function uploadConversionAdjustments(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        array $adjustments,
    ): int {
        $body = self::scope($workspaceId, $connectionId, $customerId) + ['adjustments' => $adjustments];

        return self::uploaded($this->http->post('/ads/google/conversions/adjustments', $body));
    }

    // ── GAQL ──

    /**
     * A read-only GAQL SELECT; rows come back exactly as Google returns them.
     *
     * @return array<int, array<string, mixed>>
     */
    public function query(
        string $connectionId,
        string $customerId,
        string $query,
        ?string $workspaceId = null,
    ): array {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'customerId' => $customerId,
            'query' => $query,
        ]);
        $result = self::unwrap($this->http->post('/ads/insights/query', $body));
        $rows = is_array($result) ? ($result['rows'] ?? null) : null;

        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    /** @return array<string, mixed> */
    private static function scope(string $workspaceId, string $connectionId, string $customerId): array
    {
        return [
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'customerId' => $customerId,
        ];
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private static function params(
        string $connectionId,
        string $customerId,
        ?string $workspaceId,
        array $extra = [],
    ): array {
        return self::compact([
            'workspace_id' => $workspaceId,
            'connection_id' => $connectionId,
            'customer_id' => $customerId,
        ] + $extra);
    }

    private static function id(mixed $body): string
    {
        $result = self::unwrap($body);

        return is_array($result) && is_string($result['id'] ?? null) ? $result['id'] : '';
    }

    private static function uploaded(mixed $body): int
    {
        $result = self::unwrap($body);

        return is_array($result) && is_int($result['uploaded'] ?? null) ? $result['uploaded'] : 0;
    }
}
