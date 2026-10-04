<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\AccountMove;
use Fopost\Sdk\Undefined;

/**
 * $client->googleBusiness(): manage a connected Google Business Profile location.
 *
 * Google grants Business Profile API access per project. Until that grant lands
 * on a deployment every call here fails with a 503 configuration_error.
 *
 * Responses relay Google's own shape, field for field, so they come back as
 * plain arrays rather than models we would have to keep chasing.
 */
final class GoogleBusinessResource extends Resource
{
    /** The set fetched when a caller names no metrics. */
    public const DEFAULT_DAILY_METRICS = [
        'BUSINESS_IMPRESSIONS_DESKTOP_MAPS',
        'BUSINESS_IMPRESSIONS_DESKTOP_SEARCH',
        'BUSINESS_IMPRESSIONS_MOBILE_MAPS',
        'BUSINESS_IMPRESSIONS_MOBILE_SEARCH',
        'CALL_CLICKS',
        'WEBSITE_CLICKS',
        'BUSINESS_DIRECTION_REQUESTS',
    ];

    /** @return array<string, mixed> */
    public function getLocation(string $accountId): array
    {
        return self::asArray(self::unwrap($this->http->get("/accounts/{$accountId}/gbp/location")));
    }

    /**
     * Patch the profile; an argument left out keeps its value, null clears it.
     *
     * @param array<int, string>|Undefined $additionalPhones
     * @param array<int, array<string, mixed>>|Undefined $regularHours
     * @return array<string, mixed>
     */
    public function updateLocation(
        string $accountId,
        string|Undefined $title = Undefined::Value,
        string|null|Undefined $description = Undefined::Value,
        string|null|Undefined $websiteUri = Undefined::Value,
        string|null|Undefined $primaryPhone = Undefined::Value,
        array|Undefined $additionalPhones = Undefined::Value,
        string|null|Undefined $storeCode = Undefined::Value,
        array|Undefined $regularHours = Undefined::Value,
    ): array {
        $body = [];
        $fields = [
            'title' => $title,
            'description' => $description,
            'website_uri' => $websiteUri,
            'primary_phone' => $primaryPhone,
            'additional_phones' => $additionalPhones,
            'store_code' => $storeCode,
            'regular_hours' => $regularHours,
        ];
        foreach ($fields as $key => $value) {
            if (!Undefined::is($value)) {
                $body[$key] = $value;
            }
        }

        return self::asArray(self::unwrap(
            $this->http->request('PATCH', "/accounts/{$accountId}/gbp/location", $body === [] ? (object) [] : $body),
        ));
    }

    /**
     * The attribute values set on the location, or what Google offers it.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(
        string $accountId,
        ?bool $available = null,
        ?string $categoryName = null,
        ?string $regionCode = null,
        ?string $languageCode = null,
    ): array {
        return self::asArray(self::unwrap($this->http->get("/accounts/{$accountId}/gbp/attributes", [
            'available' => $available,
            'category_name' => $categoryName,
            'region_code' => $regionCode,
            'language_code' => $languageCode,
        ])));
    }

    /**
     * Only the named attributes change; every other one is left alone.
     *
     * @param array<int, array<string, mixed>> $attributes
     * @return array<string, mixed>
     */
    public function updateAttributes(string $accountId, array $attributes): array
    {
        return self::asArray(self::unwrap(
            $this->http->request('PATCH', "/accounts/{$accountId}/gbp/attributes", ['attributes' => $attributes]),
        ));
    }

    /** @return array<string, mixed> */
    public function getMenus(string $accountId): array
    {
        return self::asArray(self::unwrap($this->http->get("/accounts/{$accountId}/gbp/menus")));
    }

    /**
     * Google has no per-section patch, so the whole menu set is replaced.
     *
     * @param array<int, array<string, mixed>> $menus
     * @return array<string, mixed>
     */
    public function replaceMenus(string $accountId, array $menus): array
    {
        return self::asArray(self::unwrap($this->http->put("/accounts/{$accountId}/gbp/menus", ['menus' => $menus])));
    }

    /** @return array<string, mixed> */
    public function getServices(string $accountId): array
    {
        return self::asArray(self::unwrap($this->http->get("/accounts/{$accountId}/gbp/services")));
    }

    /**
     * @param array<int, array<string, mixed>> $serviceItems
     * @return array<string, mixed>
     */
    public function replaceServices(string $accountId, array $serviceItems): array
    {
        return self::asArray(self::unwrap(
            $this->http->put("/accounts/{$accountId}/gbp/services", ['service_items' => $serviceItems]),
        ));
    }

    /** @return array<string, mixed> */
    public function listMedia(string $accountId, ?int $pageSize = null, ?string $pageToken = null): array
    {
        return self::asArray(self::unwrap($this->http->get("/accounts/{$accountId}/gbp/media", [
            'page_size' => $pageSize,
            'page_token' => $pageToken,
        ])));
    }

    /**
     * Add a photo from the media library; JPEG or PNG, same workspace.
     *
     * @return array<string, mixed>
     */
    public function addMedia(
        string $accountId,
        string $mediaId,
        string $category = 'ADDITIONAL',
        ?string $description = null,
    ): array {
        $body = ['media_id' => $mediaId, 'category' => $category];
        if ($description !== null) {
            $body['description'] = $description;
        }

        return self::asArray(self::unwrap($this->http->post("/accounts/{$accountId}/gbp/media", $body)));
    }

    /** @return array<string, mixed> */
    public function deleteMedia(string $accountId, string $mediaKey): array
    {
        return self::asArray(self::unwrap($this->http->delete("/accounts/{$accountId}/gbp/media/{$mediaKey}")));
    }

    /** @return array<string, mixed> */
    public function listPlaceActions(string $accountId): array
    {
        return self::asArray(self::unwrap($this->http->get("/accounts/{$accountId}/gbp/place-actions")));
    }

    /** @return array<string, mixed> */
    public function createPlaceAction(
        string $accountId,
        string $uri,
        string $placeActionType,
        ?bool $isPreferred = null,
    ): array {
        $body = ['uri' => $uri, 'place_action_type' => $placeActionType];
        if ($isPreferred !== null) {
            $body['is_preferred'] = $isPreferred;
        }

        return self::asArray(self::unwrap($this->http->post("/accounts/{$accountId}/gbp/place-actions", $body)));
    }

    /** @return array<string, mixed> */
    public function updatePlaceAction(
        string $accountId,
        string $linkId,
        string|Undefined $uri = Undefined::Value,
        bool|Undefined $isPreferred = Undefined::Value,
    ): array {
        $body = [];
        foreach (['uri' => $uri, 'is_preferred' => $isPreferred] as $key => $value) {
            if (!Undefined::is($value)) {
                $body[$key] = $value;
            }
        }

        return self::asArray(self::unwrap($this->http->request(
            'PATCH',
            "/accounts/{$accountId}/gbp/place-actions/{$linkId}",
            $body === [] ? (object) [] : $body,
        )));
    }

    /** @return array<string, mixed> */
    public function deletePlaceAction(string $accountId, string $linkId): array
    {
        return self::asArray(self::unwrap(
            $this->http->delete("/accounts/{$accountId}/gbp/place-actions/{$linkId}"),
        ));
    }

    /**
     * The ways Google will let this location be verified.
     *
     * @return array<string, mixed>
     */
    public function getVerificationOptions(string $accountId, ?string $languageCode = null): array
    {
        return self::asArray(self::unwrap($this->http->get("/accounts/{$accountId}/gbp/verification", [
            'language_code' => $languageCode,
        ])));
    }

    /**
     * The response names the pending verification to complete with the PIN.
     *
     * @return array<string, mixed>
     */
    public function startVerification(
        string $accountId,
        string $method,
        ?string $languageCode = null,
        ?string $phoneNumber = null,
        ?string $emailAddress = null,
        ?string $mailerContactName = null,
    ): array {
        $body = self::compact([
            'method' => $method,
            'language_code' => $languageCode,
            'phone_number' => $phoneNumber,
            'email_address' => $emailAddress,
            'mailer_contact_name' => $mailerContactName,
        ]);

        return self::asArray(self::unwrap($this->http->post("/accounts/{$accountId}/gbp/verification/start", $body)));
    }

    /** @return array<string, mixed> */
    public function completeVerification(string $accountId, string $verificationName, string $pin): array
    {
        return self::asArray(self::unwrap($this->http->post("/accounts/{$accountId}/gbp/verification/complete", [
            'verification_name' => $verificationName,
            'pin' => $pin,
        ])));
    }

    /**
     * Daily impressions, calls, direction requests and clicks for the range.
     *
     * @param array<int, string>|null $dailyMetrics
     * @return array<string, mixed>
     */
    public function getPerformance(
        string $accountId,
        string $startDate,
        string $endDate,
        ?array $dailyMetrics = null,
    ): array {
        return self::asArray(self::unwrap($this->http->get("/accounts/{$accountId}/gbp/performance", [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'daily_metrics' => $dailyMetrics,
        ])));
    }

    /**
     * The search terms people used to find the listing, by month.
     *
     * @return array<string, mixed>
     */
    public function getSearchKeywords(
        string $accountId,
        string $startDate,
        string $endDate,
        ?string $pageToken = null,
    ): array {
        return self::asArray(self::unwrap($this->http->get("/accounts/{$accountId}/gbp/performance", [
            'keywords' => true,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'page_token' => $pageToken,
        ])));
    }

    /** Hand the location to another workspace; the caller must own both. */
    public function assign(string $accountId, string $workspaceId): AccountMove
    {
        return AccountMove::fromArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/gbp/assign", ['workspace_id' => $workspaceId]),
        ));
    }
}
