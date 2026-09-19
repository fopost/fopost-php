<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\Ad;
use Fopost\Sdk\Model\AdConnection;
use Fopost\Sdk\Model\AdSource;
use Fopost\Sdk\Model\AudiencesResult;
use Fopost\Sdk\Model\BoostablePost;
use Fopost\Sdk\Model\CreatedAudience;
use Fopost\Sdk\Model\ExternalAd;
use Fopost\Sdk\Model\LeadFormSource;
use Fopost\Sdk\Model\LeadsPage;
use Fopost\Sdk\Model\TargetingOption;

/**
 * $client->ads(): Meta ads, audiences and lead forms.
 *
 * Every call needs the `ads` scope. boost(), create(), setStatus() and delete()
 * spend money and also need the `publish` scope.
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
     * The ad starts paused unless $paused is false.
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
}
