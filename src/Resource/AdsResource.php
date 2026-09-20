<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use DateTimeInterface;
use Fopost\Sdk\Model\Ad;
use Fopost\Sdk\Model\AdAccountTree;
use Fopost\Sdk\Model\AdCampaign;
use Fopost\Sdk\Model\AdConnection;
use Fopost\Sdk\Model\AdCreative;
use Fopost\Sdk\Model\AdInsightsReport;
use Fopost\Sdk\Model\AdLibraryPage;
use Fopost\Sdk\Model\AdProvider;
use Fopost\Sdk\Model\AdSet;
use Fopost\Sdk\Model\AdSource;
use Fopost\Sdk\Model\Audience;
use Fopost\Sdk\Model\AudiencesResult;
use Fopost\Sdk\Model\BoostablePost;
use Fopost\Sdk\Model\BidPricing;
use Fopost\Sdk\Model\BulkAdStatusResult;
use Fopost\Sdk\Model\ConversionMetrics;
use Fopost\Sdk\Model\ConversionRule;
use Fopost\Sdk\Model\CreatedAudience;
use Fopost\Sdk\Model\ExternalAd;
use Fopost\Sdk\Model\LeadFormDetail;
use Fopost\Sdk\Model\LeadFormSource;
use Fopost\Sdk\Model\LeadPage;
use Fopost\Sdk\Model\LeadsFeedPage;
use Fopost\Sdk\Model\LeadsPage;
use Fopost\Sdk\Model\NetworkAd;
use Fopost\Sdk\Model\ReachEstimate;
use Fopost\Sdk\Model\SupplyForecast;
use Fopost\Sdk\Model\TargetingOption;

/**
 * $client->ads(): ads, audiences and lead forms on a connected network.
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

    /**
     * The ad networks this deployment knows, with what each one supports.
     *
     * @return array<int, AdProvider>
     */
    public function providers(): array
    {
        return AdProvider::listFrom(self::unwrap($this->http->get('/ads/providers')));
    }

    /** The network's login URL; the caller finishes it in a browser. */
    public function authorize(
        string $provider,
        string $workspaceId,
        ?string $method = null,
        ?string $returnTo = null,
    ): string {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'method' => $method,
            'returnTo' => $returnTo,
        ]);
        $result = self::unwrap($this->http->post("/ads/connections/{$provider}/authorize", $body));

        return is_array($result) && is_string($result['url'] ?? null) ? $result['url'] : '';
    }

    /** @deprecated Use authorize('meta', ...). */
    public function authorizeMeta(string $workspaceId, ?string $method = null, ?string $returnTo = null): string
    {
        return $this->authorize('meta', $workspaceId, $method, $returnTo);
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

    /**
     * Add companies to a company-list audience. Returns the count the network took.
     * Each row needs a name, domain, pageUrl or ticker; the rows are never stored.
     *
     * @param array<int, array<string, mixed>> $companies
     */
    public function addAudienceCompanies(
        string $audienceId,
        string $workspaceId,
        string $connectionId,
        array $companies,
    ): int {
        $result = self::unwrap($this->http->request(
            'POST',
            "/ads/audiences/{$audienceId}/companies",
            ['companies' => array_values($companies)],
            self::scope($workspaceId, $connectionId),
        ));

        return is_array($result) && is_int($result['added'] ?? null) ? $result['added'] : 0;
    }

    /**
     * What the auction currently costs for that audience.
     *
     * @param array<string, mixed> $targeting
     * @param array<int, string>   $placements
     */
    public function bidPricing(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $goal,
        array $targeting,
        ?array $placements = null,
        ?string $bidType = null,
    ): BidPricing {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'goal' => $goal,
            'targeting' => $targeting,
            'placements' => $placements,
            'bidType' => $bidType,
        ]);

        return BidPricing::fromArray(self::unwrap($this->http->post('/ads/linkedin/bid-pricing', $body)));
    }

    /**
     * What that audience would deliver at that budget.
     *
     * @param array<string, mixed> $targeting
     * @param array<int, string>   $placements
     */
    public function supplyForecast(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $goal,
        array $targeting,
        ?array $placements = null,
        ?int $budgetMinor = null,
    ): SupplyForecast {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'goal' => $goal,
            'targeting' => $targeting,
            'placements' => $placements,
            'budgetMinor' => $budgetMinor,
        ]);

        return SupplyForecast::fromArray(self::unwrap($this->http->post('/ads/linkedin/supply-forecast', $body)));
    }

    /** @return array<int, ConversionRule> */
    public function conversionRules(?string $workspaceId, string $connectionId, string $adAccountId): array
    {
        return ConversionRule::listFrom(self::unwrap($this->http->get(
            '/ads/linkedin/conversion-rules',
            self::scope($workspaceId, $connectionId) + ['ad_account_id' => $adAccountId],
        )));
    }

    /** Returns the new rule's id. */
    public function createConversionRule(
        string $workspaceId,
        string $connectionId,
        string $adAccountId,
        string $name,
        string $type,
        string $attribution,
        ?int $postClickWindowDays = null,
        ?int $viewThroughWindowDays = null,
        ?int $valueMinor = null,
        ?string $currency = null,
    ): string {
        $body = self::compact([
            'workspaceId' => $workspaceId,
            'connectionId' => $connectionId,
            'adAccountId' => $adAccountId,
            'name' => $name,
            'type' => $type,
            'attribution' => $attribution,
            'postClickWindowDays' => $postClickWindowDays,
            'viewThroughWindowDays' => $viewThroughWindowDays,
            'valueMinor' => $valueMinor,
            'currency' => $currency,
        ]);
        $result = self::unwrap($this->http->post('/ads/linkedin/conversion-rules', $body));

        return is_array($result) && is_string($result['id'] ?? null) ? $result['id'] : '';
    }

    public function getConversionRule(string $ruleId, ?string $workspaceId, string $connectionId): ConversionRule
    {
        return ConversionRule::fromArray(self::unwrap($this->http->get(
            "/ads/linkedin/conversion-rules/{$ruleId}",
            self::scope($workspaceId, $connectionId),
        )));
    }

    /**
     * Change a rule. Keys are the API's own: name, type, attribution,
     * postClickWindowDays, viewThroughWindowDays, valueMinor, currency, enabled.
     *
     * @param array<string, mixed> $changes
     */
    public function updateConversionRule(
        string $ruleId,
        string $workspaceId,
        string $connectionId,
        array $changes,
    ): ConversionRule {
        return ConversionRule::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/ads/linkedin/conversion-rules/{$ruleId}",
            $changes,
            self::scope($workspaceId, $connectionId),
        )));
    }

    /** Turns the rule off; the network keeps the history. */
    public function deleteConversionRule(string $ruleId, string $workspaceId, string $connectionId): void
    {
        $this->http->request(
            'DELETE',
            "/ads/linkedin/conversion-rules/{$ruleId}",
            null,
            self::scope($workspaceId, $connectionId),
        );
    }

    public function attachConversionRule(
        string $ruleId,
        string $workspaceId,
        string $connectionId,
        string $campaignId,
    ): ConversionRule {
        return $this->association('POST', $ruleId, $workspaceId, $connectionId, $campaignId);
    }

    public function detachConversionRule(
        string $ruleId,
        string $workspaceId,
        string $connectionId,
        string $campaignId,
    ): ConversionRule {
        return $this->association('DELETE', $ruleId, $workspaceId, $connectionId, $campaignId);
    }

    /** What the rule recorded between two YYYY-MM-DD days, inclusive. */
    public function conversionMetrics(
        string $ruleId,
        ?string $workspaceId,
        string $connectionId,
        string $since,
        string $until,
    ): ConversionMetrics {
        return ConversionMetrics::fromArray(self::unwrap($this->http->get(
            "/ads/linkedin/conversion-rules/{$ruleId}/metrics",
            self::scope($workspaceId, $connectionId) + ['since' => $since, 'until' => $until],
        )));
    }

    /**
     * Send conversions back to the network. Returns how many it took. Each event
     * needs happenedAt in epoch milliseconds and an email or a clickId; the
     * address is hashed inside the API and nothing about an event is stored.
     *
     * @param array<int, array<string, mixed>> $events
     */
    public function sendConversionEvents(
        string $ruleId,
        string $workspaceId,
        string $connectionId,
        array $events,
    ): int {
        $result = self::unwrap($this->http->request(
            'POST',
            "/ads/linkedin/conversion-rules/{$ruleId}/events",
            ['events' => array_values($events)],
            self::scope($workspaceId, $connectionId),
        ));

        return is_array($result) && is_int($result['accepted'] ?? null) ? $result['accepted'] : 0;
    }

    /**
     * The network's own public ad library, not the connection's ads.
     *
     * @param array<int, string> $countries ISO 3166-1 alpha-2 codes
     */
    public function adLibrary(
        ?string $workspaceId,
        string $connectionId,
        ?string $keyword = null,
        ?string $advertiser = null,
        ?array $countries = null,
        ?string $since = null,
        ?string $until = null,
        ?string $cursor = null,
    ): AdLibraryPage {
        $query = self::compact(self::scope($workspaceId, $connectionId) + [
            'keyword' => $keyword,
            'advertiser' => $advertiser,
            'countries' => $countries === null ? null : implode(',', $countries),
            'since' => $since,
            'until' => $until,
            'cursor' => $cursor,
        ]);

        return AdLibraryPage::fromArray(self::unwrap($this->http->get('/ads/ad-library', $query)));
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

    /** @return array<string, string|null> */
    private static function scope(?string $workspaceId, string $connectionId): array
    {
        return ['workspace_id' => $workspaceId, 'connection_id' => $connectionId];
    }

    private function association(
        string $method,
        string $ruleId,
        string $workspaceId,
        string $connectionId,
        string $campaignId,
    ): ConversionRule {
        return ConversionRule::fromArray(self::unwrap($this->http->request(
            $method,
            "/ads/linkedin/conversion-rules/{$ruleId}/associations",
            ['campaignId' => $campaignId],
            self::scope($workspaceId, $connectionId),
        )));
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
