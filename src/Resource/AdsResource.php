<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use DateTimeInterface;
use Fopost\Sdk\Model\Ad;
use Fopost\Sdk\Model\AdAccountTree;
use Fopost\Sdk\Model\AdActivityResult;
use Fopost\Sdk\Model\AdCampaign;
use Fopost\Sdk\Model\AdConnection;
use Fopost\Sdk\Model\AdCreative;
use Fopost\Sdk\Model\AdInsightsReport;
use Fopost\Sdk\Model\AdLabel;
use Fopost\Sdk\Model\AdLibraryPage;
use Fopost\Sdk\Model\AdSet;
use Fopost\Sdk\Model\AdSource;
use Fopost\Sdk\Model\AdStudy;
use Fopost\Sdk\Model\Audience;
use Fopost\Sdk\Model\AudiencesResult;
use Fopost\Sdk\Model\BoostablePost;
use Fopost\Sdk\Model\BulkAdStatusResult;
use Fopost\Sdk\Model\CatalogBatchResult;
use Fopost\Sdk\Model\CatalogProductsPage;
use Fopost\Sdk\Model\CreatedAudience;
use Fopost\Sdk\Model\ExternalAd;
use Fopost\Sdk\Model\HighDemandPeriod;
use Fopost\Sdk\Model\IosCampaignLimits;
use Fopost\Sdk\Model\LeadFormDetail;
use Fopost\Sdk\Model\LeadFormSource;
use Fopost\Sdk\Model\LeadPage;
use Fopost\Sdk\Model\LeadsFeedPage;
use Fopost\Sdk\Model\LeadsPage;
use Fopost\Sdk\Model\NetworkAd;
use Fopost\Sdk\Model\PartnershipCreator;
use Fopost\Sdk\Model\ProductCatalog;
use Fopost\Sdk\Model\ProductCatalogsResult;
use Fopost\Sdk\Model\ProductFeed;
use Fopost\Sdk\Model\ProductFeedUpload;
use Fopost\Sdk\Model\ProductSet;
use Fopost\Sdk\Model\ReachEstimate;
use Fopost\Sdk\Model\ReachFrequencyPrediction;
use Fopost\Sdk\Model\ReachFrequencyResult;
use Fopost\Sdk\Model\TargetingOption;
use Fopost\Sdk\Model\ValueRuleSet;

/**
 * $client->ads(): Meta ads, catalogs, audiences, the ad archive and lead forms.
 *
 * Every call needs the `ads` scope. boost(), create(), setStatus(), delete(), bulkSetStatus() and the
 * create, update, delete and duplicate calls on campaigns, ad sets and network ads spend money and
 * also need the `publish` scope. Campaigns, ad sets, network ads and creatives are addressed by the
 * network's own ids and read live, never stored.
 */
final class AdsResource extends Resource
{
    /**
     * Boosts and ads created through FoPost, with insights from their last refresh.
     *
     * @return array<int, Ad>
     */
    public function list(?string $workspaceId = null): array
    {
        return Ad::listFrom(self::unwrap($this->http->get('/ads', ['workspace_id' => $workspaceId])));
    }

    /**
     * Ads on the connected ad accounts that were made elsewhere. Read live, never stored.
     *
     * @return array<int, ExternalAd>
     */
    public function external(?string $workspaceId = null): array
    {
        return ExternalAd::listFrom(
            self::unwrap($this->http->get('/ads/external', ['workspace_id' => $workspaceId])),
        );
    }

    /** @return array<int, BoostablePost> */
    public function boostable(?string $workspaceId = null): array
    {
        return BoostablePost::listFrom(
            self::unwrap($this->http->get('/ads/boostable', ['workspace_id' => $workspaceId])),
        );
    }

    /** @return array<int, AdConnection> */
    public function connections(?string $workspaceId = null): array
    {
        return AdConnection::listFrom(
            self::unwrap($this->http->get('/ads/connections', ['workspace_id' => $workspaceId])),
        );
    }

    /**
     * Each connection with the ad accounts and Pages its grant reaches.
     *
     * @return array<int, AdSource>
     */
    public function sources(?string $workspaceId = null): array
    {
        return AdSource::listFrom(
            self::unwrap($this->http->get('/ads/sources', ['workspace_id' => $workspaceId])),
        );
    }

    /** The Meta login URL; the caller finishes it in a browser. Method is `business` or `user`. */
    public function authorizeMeta(string $workspaceId, ?string $method = null, ?string $returnTo = null): string
    {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'method' => $method,
            'returnTo' => $returnTo,
        ]);
        $result = self::unwrap($this->http->post('/ads/connections/meta/authorize', $body));

        return is_array($result) && is_string($result['url'] ?? null) ? $result['url'] : '';
    }

    /** Also deletes every ad record created through the connection. */
    public function deleteConnection(string $connectionId, string $workspaceId): void
    {
        $this->http->request('DELETE', "/ads/connections/{$connectionId}", null, ['workspace_id' => $workspaceId]);
    }

    /**
     * Promote a post FoPost already published. Needs the `publish` scope as well as `ads`.
     * The boost starts paused unless $paused is false.
     *
     * @param array<string, mixed> $budget `{minor, type: daily|lifetime, endAt?}`
     * @param array<string, mixed> $targeting countries, ageMin, ageMax, gender, audienceIds, locations, ...
     */
    public function boost(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $postId,
        string $accountId,
        string $name,
        string $goal,
        array $budget,
        array $targeting,
        ?bool $paused = null,
    ): Ad {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'postId' => $postId,
            'accountId' => $accountId,
            'name' => $name,
            'goal' => $goal,
            'budget' => $budget,
            'targeting' => $targeting,
            'paused' => $paused,
        ]);

        return Ad::fromArray(self::unwrap($this->http->post('/ads/boost', $body)));
    }

    /**
     * Create a standalone ad from a creative. Needs the `publish` scope as well as `ads`.
     * The ad starts paused unless $paused is false. $urlTags is a query string appended to every link.
     *
     * @param array<string, mixed> $budget `{minor, type: daily|lifetime, endAt?}`
     * @param array<string, mixed> $targeting countries, ageMin, ageMax, gender, audienceIds, locations, ...
     */
    public function create(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $pageId,
        string $name,
        string $goal,
        array $budget,
        array $targeting,
        string $text,
        ?string $headline = null,
        ?string $destinationUrl = null,
        ?string $mediaUrl = null,
        ?bool $paused = null,
        ?string $urlTags = null,
    ): Ad {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'pageId' => $pageId,
            'name' => $name,
            'goal' => $goal,
            'budget' => $budget,
            'targeting' => $targeting,
            'text' => $text,
            'headline' => $headline,
            'destinationUrl' => $destinationUrl,
            'mediaUrl' => $mediaUrl,
            'paused' => $paused,
            'urlTags' => $urlTags,
        ]);

        return Ad::fromArray(self::unwrap($this->http->post('/ads', $body)));
    }

    /** Read the delivery status and lifetime insights from Meta. */
    public function refresh(string $adId, string $workspaceId): Ad
    {
        return Ad::fromArray(self::unwrap(
            $this->http->request('POST', "/ads/{$adId}/refresh", null, ['workspace_id' => $workspaceId]),
        ));
    }

    /** Set the ad `active` or `paused`. Needs the `publish` scope as well as `ads`. */
    public function setStatus(string $adId, string $workspaceId, string $status): Ad
    {
        return Ad::fromArray(self::unwrap(
            $this->http->request('PATCH', "/ads/{$adId}", ['status' => $status], ['workspace_id' => $workspaceId]),
        ));
    }

    /** End delivery and delete the ad on Meta as well as here. Needs the `publish` scope as well as `ads`. */
    public function delete(string $adId, string $workspaceId): void
    {
        $this->http->request('DELETE', "/ads/{$adId}", null, ['workspace_id' => $workspaceId]);
    }

    public function audiences(string $connectionId, string $adAccountId, ?string $workspaceId = null): AudiencesResult
    {
        return AudiencesResult::fromArray(self::unwrap($this->http->get('/ads/audiences', [
            'workspace_id' => $workspaceId,
            'connection_id' => $connectionId,
            'ad_account_id' => $adAccountId,
        ])));
    }

    /**
     * @param array<string, mixed> $spec carries a `subtype` of CUSTOM, LOOKALIKE or WEBSITE
     */
    public function createAudience(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $name,
        array $spec,
        ?string $description = null,
    ): CreatedAudience {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'name' => $name,
            'spec' => $spec,
            'description' => $description,
        ]);

        return CreatedAudience::fromArray(self::unwrap($this->http->post('/ads/audiences', $body)));
    }

    /**
     * Type is country, region, city, zip, metro, interest, behavior or income.
     *
     * @return array<int, TargetingOption>
     */
    public function searchTargeting(
        string $connectionId,
        string $type,
        ?string $q = null,
        ?string $workspaceId = null,
    ): array {
        return TargetingOption::listFrom(self::unwrap($this->http->get('/ads/targeting/search', [
            'workspace_id' => $workspaceId,
            'connection_id' => $connectionId,
            'type' => $type,
            'q' => $q,
        ])));
    }

    /** @return array<int, LeadFormSource> */
    public function leadForms(?string $workspaceId = null): array
    {
        return LeadFormSource::listFrom(
            self::unwrap($this->http->get('/ads/lead-forms', ['workspace_id' => $workspaceId])),
        );
    }

    /**
     * Create an Instant Form on the Page and return its id.
     *
     * @param array<int, string> $questions EMAIL, FULL_NAME or PHONE
     */
    public function createLeadForm(
        string $workspaceId,
        string $connectionId,
        string $pageId,
        string $name,
        array $questions,
        string $privacyPolicyUrl,
        string $thankYouMessage,
        ?string $followUpUrl = null,
    ): string {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'pageId' => $pageId,
            'name' => $name,
            'questions' => array_values($questions),
            'privacyPolicyUrl' => $privacyPolicyUrl,
            'thankYouMessage' => $thankYouMessage,
            'followUpUrl' => $followUpUrl,
        ]);
        $result = self::unwrap($this->http->post('/ads/lead-forms', $body));

        return is_array($result) && is_string($result['id'] ?? null) ? $result['id'] : '';
    }

    /** One page of leads; pass nextCursor back as $after for the next. */
    public function leads(
        string $formId,
        string $connectionId,
        string $pageId,
        ?string $after = null,
        ?string $workspaceId = null,
    ): LeadsPage {
        return LeadsPage::fromArray(self::unwrap($this->http->get("/ads/lead-forms/{$formId}/leads", [
            'workspace_id' => $workspaceId,
            'connection_id' => $connectionId,
            'page_id' => $pageId,
            'after' => $after,
        ])));
    }

    /** Every campaign on the ad account with its ad sets and ads, read live. */
    public function accountTree(string $adAccountId, string $connectionId, ?string $workspaceId = null): AdAccountTree
    {
        return AdAccountTree::fromArray(self::unwrap($this->http->get(
            "/ads/accounts/{$adAccountId}/tree",
            self::scope($workspaceId, $connectionId),
        )));
    }

    /**
     * Needs the `publish` scope as well as `ads`. Starts paused unless $paused is false.
     * Goal is engagement, traffic, awareness or video_views.
     */
    public function createCampaign(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $name,
        string $goal,
        ?bool $paused = null,
    ): AdCampaign {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'name' => $name,
            'goal' => $goal,
            'paused' => $paused,
        ]);

        return AdCampaign::fromArray(self::unwrap($this->http->post('/ads/campaigns', $body)));
    }

    public function campaign(string $campaignId, string $connectionId, ?string $workspaceId = null): AdCampaign
    {
        return AdCampaign::fromArray(self::unwrap(
            $this->http->get("/ads/campaigns/{$campaignId}", self::scope($workspaceId, $connectionId)),
        ));
    }

    /** Needs the `publish` scope as well as `ads`. Status is `active` or `paused`. */
    public function updateCampaign(
        string $campaignId,
        string $workspaceId,
        string $connectionId,
        ?string $name = null,
        ?string $status = null,
    ): AdCampaign {
        $body = self::object(['name' => $name, 'status' => $status]);

        return AdCampaign::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/ads/campaigns/{$campaignId}",
            $body,
            self::scope($workspaceId, $connectionId),
        )));
    }

    /** Needs the `publish` scope as well as `ads`. */
    public function deleteCampaign(string $campaignId, string $workspaceId, string $connectionId): void
    {
        $this->http->request('DELETE', "/ads/campaigns/{$campaignId}", null, self::scope($workspaceId, $connectionId));
    }

    /** Copy the campaign with everything under it and return the copy's id. Needs the `publish` scope. */
    public function duplicateCampaign(
        string $campaignId,
        string $workspaceId,
        string $connectionId,
        ?bool $paused = null,
    ): string {
        return $this->duplicate("/ads/campaigns/{$campaignId}/duplicate", $workspaceId, $connectionId, $paused);
    }

    /**
     * Needs the `publish` scope as well as `ads`. Starts paused unless $paused is false.
     *
     * @param array<string, mixed> $budget `{minor, type: daily|lifetime, endAt?}`
     * @param array<string, mixed> $targeting countries, ageMin, ageMax, gender, audienceIds, locations, ...
     */
    public function createAdSet(
        string $workspaceId,
        string $connectionId,
        string $campaignId,
        string $pageId,
        string $name,
        string $goal,
        array $budget,
        array $targeting,
        ?bool $paused = null,
    ): AdSet {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'campaignId' => $campaignId,
            'pageId' => $pageId,
            'name' => $name,
            'goal' => $goal,
            'budget' => $budget,
            'targeting' => $targeting,
            'paused' => $paused,
        ]);

        return AdSet::fromArray(self::unwrap($this->http->post('/ads/ad-sets', $body)));
    }

    public function adSet(string $adSetId, string $connectionId, ?string $workspaceId = null): AdSet
    {
        return AdSet::fromArray(self::unwrap(
            $this->http->get("/ads/ad-sets/{$adSetId}", self::scope($workspaceId, $connectionId)),
        ));
    }

    /**
     * Needs the `publish` scope as well as `ads`. The budget type set at creation stays.
     *
     * @param array<string, mixed>|null $targeting replaces the whole targeting spec
     */
    public function updateAdSet(
        string $adSetId,
        string $workspaceId,
        string $connectionId,
        ?string $name = null,
        ?string $status = null,
        ?int $budgetMinor = null,
        string|DateTimeInterface|null $endAt = null,
        ?array $targeting = null,
    ): AdSet {
        $body = self::object([
            'name' => $name,
            'status' => $status,
            'budgetMinor' => $budgetMinor,
            'endAt' => self::iso($endAt),
            'targeting' => $targeting,
        ]);

        return AdSet::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/ads/ad-sets/{$adSetId}",
            $body,
            self::scope($workspaceId, $connectionId),
        )));
    }

    /** Needs the `publish` scope as well as `ads`. */
    public function deleteAdSet(string $adSetId, string $workspaceId, string $connectionId): void
    {
        $this->http->request('DELETE', "/ads/ad-sets/{$adSetId}", null, self::scope($workspaceId, $connectionId));
    }

    /** Copy the ad set with its ads and return the copy's id. Needs the `publish` scope. */
    public function duplicateAdSet(
        string $adSetId,
        string $workspaceId,
        string $connectionId,
        ?bool $paused = null,
    ): string {
        return $this->duplicate("/ads/ad-sets/{$adSetId}/duplicate", $workspaceId, $connectionId, $paused);
    }

    /**
     * Create an ad inside an ad set from a creative. Distinct from create(), the one-call ad.
     * Needs the `publish` scope as well as `ads`. Starts paused unless $paused is false.
     */
    public function createNetworkAd(
        string $workspaceId,
        string $connectionId,
        string $adSetId,
        string $creativeId,
        string $name,
        ?bool $paused = null,
    ): NetworkAd {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adSetId' => $adSetId,
            'creativeId' => $creativeId,
            'name' => $name,
            'paused' => $paused,
        ]);

        return NetworkAd::fromArray(self::unwrap($this->http->post('/ads/ads', $body)));
    }

    public function networkAd(string $adId, string $connectionId, ?string $workspaceId = null): NetworkAd
    {
        return NetworkAd::fromArray(self::unwrap(
            $this->http->get("/ads/ads/{$adId}", self::scope($workspaceId, $connectionId)),
        ));
    }

    /** Needs the `publish` scope as well as `ads`. Status is `active` or `paused`. */
    public function updateNetworkAd(
        string $adId,
        string $workspaceId,
        string $connectionId,
        ?string $name = null,
        ?string $status = null,
        ?string $creativeId = null,
    ): NetworkAd {
        $body = self::object(['name' => $name, 'status' => $status, 'creativeId' => $creativeId]);

        return NetworkAd::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/ads/ads/{$adId}",
            $body,
            self::scope($workspaceId, $connectionId),
        )));
    }

    /** Needs the `publish` scope as well as `ads`. */
    public function deleteNetworkAd(string $adId, string $workspaceId, string $connectionId): void
    {
        $this->http->request('DELETE', "/ads/ads/{$adId}", null, self::scope($workspaceId, $connectionId));
    }

    /** Copy the ad and return the copy's id. Needs the `publish` scope. */
    public function duplicateNetworkAd(
        string $adId,
        string $workspaceId,
        string $connectionId,
        ?bool $paused = null,
    ): string {
        return $this->duplicate("/ads/ads/{$adId}/duplicate", $workspaceId, $connectionId, $paused);
    }

    /**
     * Set up to 50 campaigns, ad sets or ads `active` or `paused` in one call. Needs the `publish` scope.
     *
     * @param array<int, array{id: string, level: string}> $objects level is campaign, ad_set or ad
     * @return array<int, BulkAdStatusResult>
     */
    public function bulkSetStatus(string $workspaceId, string $connectionId, string $status, array $objects): array
    {
        $body = [
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'status' => $status,
            'objects' => array_values($objects),
        ];

        return BulkAdStatusResult::listFrom(self::unwrap($this->http->post('/ads/status', $body)));
    }

    /** @return array<int, AdCreative> */
    public function creatives(string $connectionId, string $adAccountId, ?string $workspaceId = null): array
    {
        $result = self::unwrap($this->http->get('/ads/creatives', [
            'workspace_id' => $workspaceId,
            'connection_id' => $connectionId,
            'ad_account_id' => $adAccountId,
        ]));

        return AdCreative::listFrom(is_array($result) ? ($result['creatives'] ?? []) : []);
    }

    /**
     * Format is image, video or carousel. A video needs $mediaUrl; a carousel needs 2 to 10 $cards.
     *
     * @param array<int, array<string, mixed>>|null $cards `{mediaUrl, destinationUrl?, headline?, description?}`
     */
    public function createCreative(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $pageId,
        string $name,
        string $format,
        string $text,
        ?string $headline = null,
        ?string $destinationUrl = null,
        ?string $callToAction = null,
        ?string $urlTags = null,
        ?string $mediaUrl = null,
        ?string $thumbnailMediaUrl = null,
        ?array $cards = null,
    ): AdCreative {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'pageId' => $pageId,
            'name' => $name,
            'format' => $format,
            'text' => $text,
            'headline' => $headline,
            'destinationUrl' => $destinationUrl,
            'callToAction' => $callToAction,
            'urlTags' => $urlTags,
            'mediaUrl' => $mediaUrl,
            'thumbnailMediaUrl' => $thumbnailMediaUrl,
            'cards' => $cards !== null ? array_values($cards) : null,
        ]);

        return AdCreative::fromArray(self::unwrap($this->http->post('/ads/creatives', $body)));
    }

    public function creative(string $creativeId, string $connectionId, ?string $workspaceId = null): AdCreative
    {
        return AdCreative::fromArray(self::unwrap(
            $this->http->get("/ads/creatives/{$creativeId}", self::scope($workspaceId, $connectionId)),
        ));
    }

    public function deleteCreative(string $creativeId, string $workspaceId, string $connectionId): void
    {
        $this->http->request('DELETE', "/ads/creatives/{$creativeId}", null, self::scope($workspaceId, $connectionId));
    }

    public function audience(string $audienceId, string $connectionId, ?string $workspaceId = null): Audience
    {
        return Audience::fromArray(self::unwrap(
            $this->http->get("/ads/audiences/{$audienceId}", self::scope($workspaceId, $connectionId)),
        ));
    }

    public function updateAudience(
        string $audienceId,
        string $workspaceId,
        string $connectionId,
        ?string $name = null,
        ?string $description = null,
    ): Audience {
        return Audience::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/ads/audiences/{$audienceId}",
            self::object(['name' => $name, 'description' => $description]),
            self::scope($workspaceId, $connectionId),
        )));
    }

    public function deleteAudience(string $audienceId, string $workspaceId, string $connectionId): void
    {
        $this->http->request('DELETE', "/ads/audiences/{$audienceId}", null, self::scope($workspaceId, $connectionId));
    }

    /**
     * Add emails to a customer list audience and return how many were sent. They are hashed by the API.
     *
     * @param array<int, string> $emails
     */
    public function addAudienceUsers(string $audienceId, string $workspaceId, string $connectionId, array $emails): int
    {
        $result = self::unwrap($this->http->request(
            'POST',
            "/ads/audiences/{$audienceId}/users",
            ['emails' => array_values($emails)],
            self::scope($workspaceId, $connectionId),
        ));

        return is_array($result) && is_int($result['added'] ?? null) ? $result['added'] : 0;
    }

    /** @param array<string, mixed> $targeting countries, ageMin, ageMax, gender, audienceIds, locations, ... */
    public function estimateReach(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $pageId,
        array $targeting,
    ): ReachEstimate {
        $body = [
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'pageId' => $pageId,
            'targeting' => $targeting,
        ];

        return ReachEstimate::fromArray(self::unwrap($this->http->post('/ads/reach-estimate', $body)));
    }

    /**
     * Insights for any campaign, ad set or ad by its network id. Breakdown is age, gender, placement
     * or country; $daily adds a per-day timeline.
     */
    public function insights(
        string $connectionId,
        string $objectId,
        string $since,
        string $until,
        ?string $breakdown = null,
        ?bool $daily = null,
        ?string $workspaceId = null,
    ): AdInsightsReport {
        return AdInsightsReport::fromArray(self::unwrap($this->http->get('/ads/insights', [
            'workspace_id' => $workspaceId,
            'connection_id' => $connectionId,
            'object_id' => $objectId,
            'since' => $since,
            'until' => $until,
            'breakdown' => $breakdown,
            'daily' => $daily,
        ])));
    }

    /** Insights for a boost or ad created through FoPost, by its FoPost id. */
    public function adInsights(
        string $adId,
        string $workspaceId,
        string $since,
        string $until,
        ?string $breakdown = null,
        ?bool $daily = null,
    ): AdInsightsReport {
        return AdInsightsReport::fromArray(self::unwrap($this->http->get("/ads/{$adId}/insights", [
            'workspace_id' => $workspaceId,
            'since' => $since,
            'until' => $until,
            'breakdown' => $breakdown,
            'daily' => $daily,
        ])));
    }

    public function leadForm(
        string $formId,
        string $connectionId,
        string $pageId,
        ?string $workspaceId = null,
    ): LeadFormDetail {
        return LeadFormDetail::fromArray(self::unwrap($this->http->get("/ads/lead-forms/{$formId}", [
            'workspace_id' => $workspaceId,
            'connection_id' => $connectionId,
            'page_id' => $pageId,
        ])));
    }

    /** Stop the form taking new leads. */
    public function archiveLeadForm(
        string $formId,
        string $workspaceId,
        string $connectionId,
        string $pageId,
    ): LeadFormDetail {
        $body = ['workspaceId' => $workspaceId, 'connectionId' => $connectionId, 'pageId' => $pageId];

        return LeadFormDetail::fromArray(self::unwrap($this->http->post("/ads/lead-forms/{$formId}/archive", $body)));
    }

    /** One page of leads stored from subscribed Pages; pass nextCursor back as $cursor for the next. */
    public function leadsFeed(
        ?string $workspaceId = null,
        ?string $formId = null,
        ?string $pageId = null,
        ?string $cursor = null,
        ?int $limit = null,
    ): LeadsFeedPage {
        return LeadsFeedPage::fromArray(self::unwrap($this->http->get('/ads/leads', [
            'workspace_id' => $workspaceId,
            'form_id' => $formId,
            'page_id' => $pageId,
            'cursor' => $cursor,
            'limit' => $limit,
        ])));
    }

    /** @return array<int, LeadPage> */
    public function leadPages(?string $workspaceId = null): array
    {
        return LeadPage::listFrom(self::unwrap($this->http->get('/ads/lead-pages', ['workspace_id' => $workspaceId])));
    }

    /** Subscribe a Page to new leads and return how many existing leads were backfilled. */
    public function subscribeLeadPage(string $workspaceId, string $connectionId, string $pageId): int
    {
        $body = ['workspaceId' => $workspaceId, 'connectionId' => $connectionId, 'pageId' => $pageId];
        $result = self::unwrap($this->http->post('/ads/lead-pages', $body));

        return is_array($result) && is_int($result['backfilled'] ?? null) ? $result['backfilled'] : 0;
    }

    public function unsubscribeLeadPage(string $pageId, string $workspaceId, string $connectionId): void
    {
        $this->http->request('DELETE', "/ads/lead-pages/{$pageId}", null, self::scope($workspaceId, $connectionId));
    }

    // ─── Goals ──────────────────────────────────────────────────────

    /**
     * The goals this connection's network can run right now. Ask rather than assume:
     * a goal the deployment is not set up for is absent here and is refused if sent.
     *
     * @return array<int, string>
     */
    public function goals(string $connectionId, ?string $workspaceId = null): array
    {
        $result = self::unwrap($this->http->get('/ads/goals', self::scope($workspaceId, $connectionId)));

        return is_array($result) ? array_values(array_filter($result, 'is_string')) : [];
    }

    // ─── Product catalogs ───────────────────────────────────────────

    /** Catalogs the connection's business portfolios reach. Read live, never stored. */
    public function catalogs(string $connectionId, ?string $workspaceId = null): ProductCatalogsResult
    {
        return ProductCatalogsResult::fromArray(
            self::unwrap($this->http->get('/ads/catalogs', self::scope($workspaceId, $connectionId)))
        );
    }

    /** Created on the connection's business portfolio. Also needs the `publish` scope. */
    public function createCatalog(
        string $workspaceId,
        string $connectionId,
        string $name,
        ?string $vertical = null,
    ): ProductCatalog {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'name' => $name,
            'vertical' => $vertical,
        ]);

        return ProductCatalog::fromArray(self::unwrap($this->http->post('/ads/catalogs', $body)));
    }

    public function getCatalog(string $catalogId, string $connectionId, ?string $workspaceId = null): ProductCatalog
    {
        return ProductCatalog::fromArray(
            self::unwrap($this->http->get("/ads/catalogs/{$catalogId}", self::scope($workspaceId, $connectionId)))
        );
    }

    /** Also needs the `publish` scope. */
    public function updateCatalog(
        string $catalogId,
        string $workspaceId,
        string $connectionId,
        string $name,
    ): ProductCatalog {
        $body = ['workspaceId' => $workspaceId, 'connectionId' => $connectionId, 'name' => $name];

        return ProductCatalog::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/ads/catalogs/{$catalogId}",
            $body,
            self::scope($workspaceId, $connectionId),
        )));
    }

    /** Deletes every product, feed and set in it. Also needs the `publish` scope. */
    public function deleteCatalog(string $catalogId, string $workspaceId, string $connectionId): void
    {
        $this->http->request('DELETE', "/ads/catalogs/{$catalogId}", null, self::scope($workspaceId, $connectionId));
    }

    /** One page of products; pass `nextCursor` back as `$after`. */
    public function catalogProducts(
        string $catalogId,
        string $connectionId,
        ?string $workspaceId = null,
        ?string $after = null,
    ): CatalogProductsPage {
        $query = self::scope($workspaceId, $connectionId) + ['after' => $after];

        return CatalogProductsPage::fromArray(
            self::unwrap($this->http->get("/ads/catalogs/{$catalogId}/products", $query))
        );
    }

    /**
     * Up to 500 upserts and deletes in one batch, keyed by your own `retailerId`.
     * Also needs the `publish` scope.
     *
     * @param array<int, array<string, mixed>> $products
     */
    public function writeCatalogProducts(
        string $catalogId,
        string $workspaceId,
        string $connectionId,
        array $products,
    ): CatalogBatchResult {
        $body = [
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'products' => array_values($products),
        ];

        return CatalogBatchResult::fromArray(
            self::unwrap($this->http->post("/ads/catalogs/{$catalogId}/products", $body))
        );
    }

    /** @return array<int, ProductFeed> */
    public function productFeeds(string $catalogId, string $connectionId, ?string $workspaceId = null): array
    {
        return ProductFeed::listFrom(
            self::unwrap($this->http->get("/ads/catalogs/{$catalogId}/feeds", self::scope($workspaceId, $connectionId)))
        );
    }

    /** `$schedule` is HOURLY, DAILY or WEEKLY and needs `$url`. Also needs the `publish` scope. */
    public function createProductFeed(
        string $catalogId,
        string $workspaceId,
        string $connectionId,
        string $name,
        ?string $url = null,
        ?string $schedule = null,
    ): ProductFeed {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'name' => $name,
            'url' => $url,
            'schedule' => $schedule,
        ]);

        return ProductFeed::fromArray(self::unwrap($this->http->post("/ads/catalogs/{$catalogId}/feeds", $body)));
    }

    /** Also needs the `publish` scope. */
    public function deleteProductFeed(
        string $catalogId,
        string $feedId,
        string $workspaceId,
        string $connectionId,
    ): void {
        $this->http->request(
            'DELETE',
            "/ads/catalogs/{$catalogId}/feeds/{$feedId}",
            null,
            self::scope($workspaceId, $connectionId),
        );
    }

    /**
     * Each run the network made of the feed.
     *
     * @return array<int, ProductFeedUpload>
     */
    public function feedUploads(
        string $catalogId,
        string $feedId,
        string $connectionId,
        ?string $workspaceId = null,
    ): array {
        return ProductFeedUpload::listFrom(self::unwrap($this->http->get(
            "/ads/catalogs/{$catalogId}/feeds/{$feedId}/uploads",
            self::scope($workspaceId, $connectionId),
        )));
    }

    /** Fetches the feed now; the id of the run. Also needs the `publish` scope. */
    public function startFeedUpload(
        string $catalogId,
        string $feedId,
        string $workspaceId,
        string $connectionId,
        ?string $url = null,
    ): string {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'url' => $url,
        ]);
        $result = self::unwrap($this->http->post("/ads/catalogs/{$catalogId}/feeds/{$feedId}/uploads", $body));
        $id = is_array($result) ? ($result['id'] ?? null) : null;

        return is_string($id) ? $id : '';
    }

    /**
     * A catalog ad runs from a product set, not the whole catalog.
     *
     * @return array<int, ProductSet>
     */
    public function productSets(string $catalogId, string $connectionId, ?string $workspaceId = null): array
    {
        return ProductSet::listFrom(self::unwrap($this->http->get(
            "/ads/catalogs/{$catalogId}/product-sets",
            self::scope($workspaceId, $connectionId),
        )));
    }

    /**
     * Without a `$filter` the set is the whole catalog. Also needs the `publish` scope.
     *
     * @param array<string, mixed>|null $filter
     */
    public function createProductSet(
        string $catalogId,
        string $workspaceId,
        string $connectionId,
        string $name,
        ?array $filter = null,
    ): ProductSet {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'name' => $name,
            'filter' => $filter,
        ]);

        return ProductSet::fromArray(
            self::unwrap($this->http->post("/ads/catalogs/{$catalogId}/product-sets", $body))
        );
    }

    /**
     * Also needs the `publish` scope.
     *
     * @param array<string, mixed>|null $filter
     */
    public function updateProductSet(
        string $catalogId,
        string $setId,
        string $workspaceId,
        string $connectionId,
        string $name,
        ?array $filter = null,
    ): ProductSet {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'name' => $name,
            'filter' => $filter,
        ]);

        return ProductSet::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/ads/catalogs/{$catalogId}/product-sets/{$setId}",
            $body,
            self::scope($workspaceId, $connectionId),
        )));
    }

    /** Also needs the `publish` scope. */
    public function deleteProductSet(
        string $catalogId,
        string $setId,
        string $workspaceId,
        string $connectionId,
    ): void {
        $this->http->request(
            'DELETE',
            "/ads/catalogs/{$catalogId}/product-sets/{$setId}",
            null,
            self::scope($workspaceId, $connectionId),
        );
    }

    // ─── Reach and frequency ────────────────────────────────────────

    public function reachFrequency(
        string $connectionId,
        string $adAccountId,
        ?string $workspaceId = null,
    ): ReachFrequencyResult {
        return ReachFrequencyResult::fromArray(self::unwrap($this->http->get(
            '/ads/reach-frequency',
            self::account($workspaceId, $connectionId, $adAccountId),
        )));
    }

    /**
     * Prices a flight. Nothing is bought until you reserve it.
     *
     * @param array<string, mixed> $targeting
     * @param array<int, string> $placements
     */
    public function createReachFrequency(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $name,
        array $targeting,
        array $placements,
        int $budgetMinor,
        DateTimeInterface|string $startAt,
        DateTimeInterface|string $endAt,
        ?int $frequencyCap = null,
    ): ReachFrequencyPrediction {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'name' => $name,
            'targeting' => $targeting,
            'placements' => array_values($placements),
            'budgetMinor' => $budgetMinor,
            'startAt' => self::iso($startAt),
            'endAt' => self::iso($endAt),
            'frequencyCap' => $frequencyCap,
        ]);

        return ReachFrequencyPrediction::fromArray(self::unwrap($this->http->post('/ads/reach-frequency', $body)));
    }

    public function getReachFrequency(
        string $predictionId,
        string $connectionId,
        string $adAccountId,
        ?string $workspaceId = null,
    ): ReachFrequencyPrediction {
        return ReachFrequencyPrediction::fromArray(self::unwrap($this->http->get(
            "/ads/reach-frequency/{$predictionId}",
            self::account($workspaceId, $connectionId, $adAccountId),
        )));
    }

    /** Holds the inventory the prediction priced. Also needs the `publish` scope. */
    public function reserveReachFrequency(
        string $predictionId,
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
    ): ReachFrequencyPrediction {
        return $this->reachFrequencyAction($predictionId, 'reserve', $workspaceId, $connectionId, $adAccountId);
    }

    /** Also needs the `publish` scope. */
    public function cancelReachFrequency(
        string $predictionId,
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
    ): ReachFrequencyPrediction {
        return $this->reachFrequencyAction($predictionId, 'cancel', $workspaceId, $connectionId, $adAccountId);
    }

    // ─── Ad Library ─────────────────────────────────────────────────

    /**
     * The public ad archive: ads anyone is running, by keyword or by Page. Read live on
     * every call and stored nowhere, so an ad that stops running is simply absent next time.
     *
     * @param array<int, string> $countries
     * @param array<int, string>|null $pageIds
     */
    public function library(
        string $connectionId,
        array $countries,
        ?string $workspaceId = null,
        ?string $q = null,
        ?array $pageIds = null,
        ?string $activeStatus = null,
        ?int $limit = null,
        ?string $after = null,
    ): AdLibraryPage {
        $query = self::scope($workspaceId, $connectionId) + [
            'countries' => implode(',', $countries),
            'q' => $q,
            'page_ids' => $pageIds !== null ? implode(',', $pageIds) : null,
            'active_status' => $activeStatus,
            'limit' => $limit !== null ? (string) $limit : null,
            'after' => $after,
        ];

        return AdLibraryPage::fromArray(self::unwrap($this->http->get('/ads/library', $query)));
    }

    // ─── Partnership ads ────────────────────────────────────────────

    /**
     * Creators who allowlisted this Page to run partnership ads on their posts.
     *
     * @return array<int, PartnershipCreator>
     */
    public function partnershipCreators(string $connectionId, string $pageId, ?string $workspaceId = null): array
    {
        $query = self::scope($workspaceId, $connectionId) + ['page_id' => $pageId];

        return PartnershipCreator::listFrom(self::unwrap($this->http->get('/ads/partnership/creators', $query)));
    }

    /**
     * Asks a creator for permission; the list as it now stands.
     *
     * @return array<int, PartnershipCreator>
     */
    public function requestPartnership(
        string $workspaceId,
        string $connectionId,
        string $pageId,
        string $creatorId,
    ): array {
        $body = [
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'pageId' => $pageId,
            'creatorId' => $creatorId,
        ];

        return PartnershipCreator::listFrom(self::unwrap($this->http->post('/ads/partnership/creators', $body)));
    }

    public function revokePartnership(
        string $creatorId,
        string $workspaceId,
        string $connectionId,
        string $pageId,
    ): void {
        $query = self::scope($workspaceId, $connectionId) + ['page_id' => $pageId];
        $this->http->request('DELETE', "/ads/partnership/creators/{$creatorId}", null, $query);
    }

    // ─── Ad account settings ────────────────────────────────────────

    /** Who changed what on the ad account, and when. Dates are `YYYY-MM-DD`. */
    public function accountActivity(
        string $connectionId,
        string $adAccountId,
        ?string $workspaceId = null,
        ?string $since = null,
        ?string $until = null,
    ): AdActivityResult {
        $query = self::account($workspaceId, $connectionId, $adAccountId) + ['since' => $since, 'until' => $until];

        return AdActivityResult::fromArray(self::unwrap($this->http->get('/ads/account/activity', $query)));
    }

    /** @return array<int, AdLabel> */
    public function labels(string $connectionId, string $adAccountId, ?string $workspaceId = null): array
    {
        return AdLabel::listFrom(self::unwrap($this->http->get(
            '/ads/account/labels',
            self::account($workspaceId, $connectionId, $adAccountId),
        )));
    }

    public function createLabel(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $name,
    ): AdLabel {
        return AdLabel::fromArray(self::unwrap($this->http->post('/ads/account/labels', [
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'name' => $name,
        ])));
    }

    public function updateLabel(
        string $labelId,
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $name,
    ): AdLabel {
        $body = [
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'name' => $name,
        ];

        return AdLabel::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/ads/account/labels/{$labelId}",
            $body,
            self::scope($workspaceId, $connectionId),
        )));
    }

    public function deleteLabel(
        string $labelId,
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
    ): void {
        $this->http->request(
            'DELETE',
            "/ads/account/labels/{$labelId}",
            null,
            self::account($workspaceId, $connectionId, $adAccountId),
        );
    }

    /** Keeps whatever labels the object already carries. `$level` is campaign, ad_set or ad. */
    public function applyLabel(
        string $labelId,
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $objectId,
        string $level,
    ): void {
        $this->http->post("/ads/account/labels/{$labelId}/apply", [
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'objectId' => $objectId,
            'level' => $level,
        ]);
    }

    /** @return array<int, AdStudy> */
    public function studies(string $connectionId, string $adAccountId, ?string $workspaceId = null): array
    {
        return AdStudy::listFrom(self::unwrap($this->http->get(
            '/ads/account/studies',
            self::account($workspaceId, $connectionId, $adAccountId),
        )));
    }

    /**
     * Splits traffic evenly across two to five cells of `name` and `objectIds`.
     *
     * @param array<int, array<string, mixed>> $cells
     */
    public function createStudy(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $name,
        DateTimeInterface|string $startAt,
        DateTimeInterface|string $endAt,
        array $cells,
        ?string $description = null,
    ): AdStudy {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'name' => $name,
            'startAt' => self::iso($startAt),
            'endAt' => self::iso($endAt),
            'cells' => array_values($cells),
            'description' => $description,
        ]);

        return AdStudy::fromArray(self::unwrap($this->http->post('/ads/account/studies', $body)));
    }

    public function getStudy(
        string $studyId,
        string $connectionId,
        string $adAccountId,
        ?string $workspaceId = null,
    ): AdStudy {
        return AdStudy::fromArray(self::unwrap($this->http->get(
            "/ads/account/studies/{$studyId}",
            self::account($workspaceId, $connectionId, $adAccountId),
        )));
    }

    public function deleteStudy(
        string $studyId,
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
    ): void {
        $this->http->request(
            'DELETE',
            "/ads/account/studies/{$studyId}",
            null,
            self::account($workspaceId, $connectionId, $adAccountId),
        );
    }

    /**
     * How many iOS 14 campaigns the account may run at once, per app.
     *
     * @return array<int, IosCampaignLimits>
     */
    public function iosCampaignLimits(string $connectionId, string $adAccountId, ?string $workspaceId = null): array
    {
        return IosCampaignLimits::listFrom(self::unwrap($this->http->get(
            '/ads/account/ios-limits',
            self::account($workspaceId, $connectionId, $adAccountId),
        )));
    }

    /** @return array<int, HighDemandPeriod> */
    public function highDemandPeriods(string $connectionId, string $adAccountId, ?string $workspaceId = null): array
    {
        return HighDemandPeriod::listFrom(self::unwrap($this->http->get(
            '/ads/account/high-demand-periods',
            self::account($workspaceId, $connectionId, $adAccountId),
        )));
    }

    /**
     * Tells the network to expect heavier spend over a window, so pacing allows for it.
     * `$budgetValueType` is ABSOLUTE or MULTIPLIER.
     */
    public function createHighDemandPeriod(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        DateTimeInterface|string $startAt,
        DateTimeInterface|string $endAt,
        float $budgetValue,
        string $budgetValueType,
    ): HighDemandPeriod {
        return HighDemandPeriod::fromArray(self::unwrap($this->http->post('/ads/account/high-demand-periods', [
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'startAt' => self::iso($startAt),
            'endAt' => self::iso($endAt),
            'budgetValue' => $budgetValue,
            'budgetValueType' => $budgetValueType,
        ])));
    }

    public function deleteHighDemandPeriod(
        string $periodId,
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
    ): void {
        $this->http->request(
            'DELETE',
            "/ads/account/high-demand-periods/{$periodId}",
            null,
            self::account($workspaceId, $connectionId, $adAccountId),
        );
    }

    /** @return array<int, ValueRuleSet> */
    public function valueRuleSets(string $connectionId, string $adAccountId, ?string $workspaceId = null): array
    {
        return ValueRuleSet::listFrom(self::unwrap($this->http->get(
            '/ads/account/value-rule-sets',
            self::account($workspaceId, $connectionId, $adAccountId),
        )));
    }

    /**
     * Weights conversions so some audiences count for more than others.
     *
     * @param array<int, array<string, mixed>> $rules
     */
    public function createValueRuleSet(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $name,
        array $rules,
    ): ValueRuleSet {
        return ValueRuleSet::fromArray(self::unwrap($this->http->post('/ads/account/value-rule-sets', [
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'name' => $name,
            'rules' => array_values($rules),
        ])));
    }

    public function deleteValueRuleSet(
        string $ruleSetId,
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
    ): void {
        $this->http->request(
            'DELETE',
            "/ads/account/value-rule-sets/{$ruleSetId}",
            null,
            self::account($workspaceId, $connectionId, $adAccountId),
        );
    }

    private function reachFrequencyAction(
        string $predictionId,
        string $action,
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
    ): ReachFrequencyPrediction {
        $body = [
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
        ];

        return ReachFrequencyPrediction::fromArray(
            self::unwrap($this->http->post("/ads/reach-frequency/{$predictionId}/{$action}", $body))
        );
    }

    /** @return array<string, string|null> */
    private static function account(?string $workspaceId, string $connectionId, string $adAccountId): array
    {
        return self::scope($workspaceId, $connectionId) + ['ad_account_id' => $adAccountId];
    }

    /** @return array<string, string|null> */
    private static function scope(?string $workspaceId, string $connectionId): array
    {
        return ['workspace_id' => $workspaceId, 'connection_id' => $connectionId];
    }

    /**
     * The API rejects a missing or array body, so an empty one goes out as `{}`.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>|\stdClass
     */
    private static function object(array $body): array|\stdClass
    {
        $body = self::compact($body);

        return $body === [] ? new \stdClass() : $body;
    }

    private function duplicate(string $path, string $workspaceId, string $connectionId, ?bool $paused): string
    {
        $result = self::unwrap($this->http->request(
            'POST',
            $path,
            self::object(['paused' => $paused]),
            self::scope($workspaceId, $connectionId),
        ));

        return is_array($result) && is_string($result['id'] ?? null) ? $result['id'] : '';
    }
}
