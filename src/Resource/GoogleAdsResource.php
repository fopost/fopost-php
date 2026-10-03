<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\GoogleAdScheduleSlot;
use Fopost\Sdk\Model\GoogleAssetGroup;
use Fopost\Sdk\Model\GoogleAssetsResult;
use Fopost\Sdk\Model\GoogleBidStrategy;
use Fopost\Sdk\Model\GoogleConversionAction;
use Fopost\Sdk\Model\GoogleKeyword;
use Fopost\Sdk\Model\GoogleKeywordIdea;
use Fopost\Sdk\Model\GoogleLocalServicesLead;
use Fopost\Sdk\Model\GoogleSearchTerm;
use Fopost\Sdk\Model\GoogleSharedSet;

/**
 * $client->ads()->google(): the Google Ads surface no other network has.
 *
 * Campaigns, ad groups, ads, audiences and insights are on the ads resource itself and dispatch by
 * connection. What is here — keywords, assets, Performance Max asset groups, Local Services leads,
 * conversions and raw GAQL — is Google only, and a connection on another network answers 400.
 *
 * Every call needs the `ads` scope; anything that changes what a live account serves or bids also
 * needs `publish`. $customerId is digits only and has to name an account the connection's grant
 * reaches: any other answers 404.
 */
final class GoogleAdsResource extends Resource
{
    // ── Keywords ──

    /**
     * Keywords on the account, or on one ad group.
     *
     * @return array<int, GoogleKeyword>
     */
    public function keywords(
        string $connectionId,
        string $customerId,
        ?string $workspaceId = null,
        ?string $adGroupId = null,
    ): array {
        return GoogleKeyword::listFrom(self::unwrap($this->http->get(
            '/ads/google/keywords',
            self::params($connectionId, $customerId, $workspaceId, ['ad_group_id' => $adGroupId]),
        )));
    }

    /** The new keyword's id. Needs the `publish` scope as well as `ads`. */
    public function createKeyword(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        string $adGroupId,
        string $text,
        string $matchType,
        ?int $cpcBidMinor = null,
    ): string {
        $body = self::scope($workspaceId, $connectionId, $customerId) + self::compact([
            'adGroupId' => $adGroupId,
            'text' => $text,
            'matchType' => $matchType,
            'cpcBidMinor' => $cpcBidMinor,
        ]);

        return self::id($this->http->post('/ads/google/keywords', $body));
    }

    /** $status is `active` or `paused`. Needs the `publish` scope as well as `ads`. */
    public function updateKeyword(
        string $keywordId,
        string $workspaceId,
        string $connectionId,
        string $customerId,
        ?string $status = null,
        ?int $cpcBidMinor = null,
    ): string {
        $body = self::scope($workspaceId, $connectionId, $customerId) + self::compact([
            'status' => $status,
            'cpcBidMinor' => $cpcBidMinor,
        ]);

        return self::id($this->http->request('PATCH', "/ads/google/keywords/{$keywordId}", $body));
    }

    /** Needs the `publish` scope as well as `ads`. */
    public function deleteKeyword(
        string $keywordId,
        string $workspaceId,
        string $connectionId,
        string $customerId,
    ): void {
        $this->http->request(
            'DELETE',
            "/ads/google/keywords/{$keywordId}",
            self::scope($workspaceId, $connectionId, $customerId),
        );
    }

    /**
     * Ideas from seed keywords, a landing page, or both.
     *
     * @param array<int, string>|null $seeds
     * @param array<int, string>|null $geoTargetIds
     * @return array<int, GoogleKeywordIdea>
     */
    public function keywordIdeas(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        ?array $seeds = null,
        ?string $url = null,
        ?string $languageId = null,
        ?array $geoTargetIds = null,
    ): array {
        $body = self::scope($workspaceId, $connectionId, $customerId) + self::compact([
            'seeds' => $seeds,
            'url' => $url,
            'languageId' => $languageId,
            'geoTargetIds' => $geoTargetIds,
        ]);

        return GoogleKeywordIdea::listFrom(
            self::unwrap($this->http->post('/ads/google/keyword-ideas', $body)),
        );
    }

    /**
     * @param array<int, string> $keywords
     * @return array<int, GoogleKeywordIdea>
     */
    public function keywordMetrics(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        array $keywords,
    ): array {
        $body = self::scope($workspaceId, $connectionId, $customerId) + ['keywords' => $keywords];

        return GoogleKeywordIdea::listFrom(
            self::unwrap($this->http->post('/ads/google/keyword-metrics', $body)),
        );
    }

    /**
     * What people actually searched, with the metrics each term earned.
     *
     * @return array<int, GoogleSearchTerm>
     */
    public function searchTerms(
        string $connectionId,
        string $customerId,
        string $since,
        string $until,
        ?string $workspaceId = null,
    ): array {
        return GoogleSearchTerm::listFrom(self::unwrap($this->http->get(
            '/ads/google/search-terms',
            self::params($connectionId, $customerId, $workspaceId, ['since' => $since, 'until' => $until]),
        )));
    }

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

    // ── Negative keyword lists ──

    /** @return array<int, GoogleSharedSet> */
    public function negativeKeywordLists(
        string $connectionId,
        string $customerId,
        ?string $workspaceId = null,
    ): array {
        return GoogleSharedSet::listFrom(self::unwrap($this->http->get(
            '/ads/google/negative-keywords',
            self::params($connectionId, $customerId, $workspaceId),
        )));
    }

    /** Needs the `publish` scope as well as `ads`. */
    public function createNegativeKeywordList(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        string $name,
    ): string {
        $body = self::scope($workspaceId, $connectionId, $customerId) + ['name' => $name];

        return self::id($this->http->post('/ads/google/negative-keywords', $body));
    }

    /**
     * How many were added. Needs the `publish` scope as well as `ads`.
     *
     * @param array<int, array<string, mixed>> $keywords
     */
    public function addNegativeKeywords(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        string $sharedSetId,
        array $keywords,
    ): int {
        $body = self::scope($workspaceId, $connectionId, $customerId) + [
            'sharedSetId' => $sharedSetId,
            'keywords' => $keywords,
        ];
        $result = self::unwrap($this->http->post('/ads/google/negative-keywords/keywords', $body));

        return is_array($result) && is_int($result['added'] ?? null) ? $result['added'] : 0;
    }

    /** Needs the `publish` scope as well as `ads`. */
    public function attachNegativeKeywordList(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        string $sharedSetId,
        string $campaignId,
    ): void {
        $body = self::scope($workspaceId, $connectionId, $customerId) + [
            'sharedSetId' => $sharedSetId,
            'campaignId' => $campaignId,
        ];
        $this->http->post('/ads/google/negative-keywords/attach', $body);
    }

    // ── Assets ──

    /** Sitelinks, callouts and snippets, with the links that put each one under an ad. */
    public function assets(
        string $connectionId,
        string $customerId,
        ?string $workspaceId = null,
    ): GoogleAssetsResult {
        return GoogleAssetsResult::fromArray(self::unwrap($this->http->get(
            '/ads/google/assets',
            self::params($connectionId, $customerId, $workspaceId),
        )));
    }

    /**
     * $spec is a sitelink, callout or snippet. Needs the `publish` scope as well as `ads`.
     *
     * @param array<string, mixed> $spec
     */
    public function createAsset(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        array $spec,
    ): string {
        $body = self::scope($workspaceId, $connectionId, $customerId) + ['spec' => $spec];

        return self::id($this->http->post('/ads/google/assets', $body));
    }

    /** Attaches to the account when $campaignId is left out. Needs `publish`. */
    public function attachAsset(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        string $assetId,
        string $fieldType,
        ?string $campaignId = null,
    ): void {
        $body = self::scope($workspaceId, $connectionId, $customerId) + self::compact([
            'assetId' => $assetId,
            'fieldType' => $fieldType,
            'campaignId' => $campaignId,
        ]);
        $this->http->post('/ads/google/assets/attach', $body);
    }

    /**
     * Removes the links that put the asset under an ad; on Google the asset itself is permanent.
     * Needs the `publish` scope as well as `ads`.
     */
    public function deleteAsset(
        string $assetId,
        string $workspaceId,
        string $connectionId,
        string $customerId,
    ): void {
        $this->http->request(
            'DELETE',
            "/ads/google/assets/{$assetId}",
            self::scope($workspaceId, $connectionId, $customerId),
        );
    }

    // ── Performance Max asset groups ──

    /** @return array<int, GoogleAssetGroup> */
    public function assetGroups(
        string $connectionId,
        string $customerId,
        ?string $workspaceId = null,
        ?string $campaignId = null,
    ): array {
        return GoogleAssetGroup::listFrom(self::unwrap($this->http->get(
            '/ads/google/asset-groups',
            self::params($connectionId, $customerId, $workspaceId, ['campaign_id' => $campaignId]),
        )));
    }

    /**
     * Starts paused unless $status says otherwise. Needs `publish` as well as `ads`.
     *
     * @param array<int, string> $finalUrls
     */
    public function createAssetGroup(
        string $workspaceId,
        string $connectionId,
        string $customerId,
        string $campaignId,
        string $name,
        array $finalUrls,
        ?string $status = null,
    ): string {
        $body = self::scope($workspaceId, $connectionId, $customerId) + self::compact([
            'campaignId' => $campaignId,
            'name' => $name,
            'finalUrls' => $finalUrls,
            'status' => $status,
        ]);

        return self::id($this->http->post('/ads/google/asset-groups', $body));
    }

    /** Needs the `publish` scope as well as `ads`. */
    public function updateAssetGroup(
        string $assetGroupId,
        string $workspaceId,
        string $connectionId,
        string $customerId,
        ?string $name = null,
        ?string $status = null,
    ): string {
        $body = self::scope($workspaceId, $connectionId, $customerId) + self::compact([
            'name' => $name,
            'status' => $status,
        ]);

        return self::id(
            $this->http->request('PATCH', "/ads/google/asset-groups/{$assetGroupId}", $body),
        );
    }

    /** Needs the `publish` scope as well as `ads`. */
    public function deleteAssetGroup(
        string $assetGroupId,
        string $workspaceId,
        string $connectionId,
        string $customerId,
    ): void {
        $this->http->request(
            'DELETE',
            "/ads/google/asset-groups/{$assetGroupId}",
            self::scope($workspaceId, $connectionId, $customerId),
        );
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
