<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\Workspace;
use Fopost\Sdk\Model\WorkspaceAnalytics;
use Fopost\Sdk\Undefined;

/** $client->workspaces(): the workspaces the key can reach. */
final class WorkspacesResource extends Resource
{
    /** @return array<int, Workspace> */
    public function list(): array
    {
        return Workspace::listFrom(self::unwrap($this->http->get('/workspaces')));
    }

    public function get(string $workspaceId): Workspace
    {
        return Workspace::fromArray(self::unwrap($this->http->get("/workspaces/{$workspaceId}")));
    }

    /** The latest profile numbers of every account in the workspace, and their sum. */
    public function analytics(string $workspaceId): WorkspaceAnalytics
    {
        return WorkspaceAnalytics::fromArray(self::unwrap($this->http->get("/workspaces/{$workspaceId}/analytics")));
    }

    /** `$type` is PERSONAL, TEAM, ORGANIZATION, CLIENT, PROJECT, DEPARTMENT, EVENT, TEMPORARY, COMMUNITY, BRAND or AGENCY. */
    public function create(
        string $name,
        ?string $type = null,
        ?string $timezone = null,
        ?string $language = null,
        ?string $logo = null,
        ?string $website = null,
        ?string $country = null,
        ?string $description = null,
    ): Workspace {
        $body = self::compact([
            'name' => $name,
            'type' => $type,
            'timezone' => $timezone,
            'language' => $language,
            'logo' => $logo,
            'website' => $website,
            'country' => $country,
            'description' => $description,
        ]);

        return Workspace::fromArray(self::unwrap($this->http->post('/workspaces', $body)));
    }

    /**
     * Partial update: only the fields you pass are sent. The settings are camelCase on the wire.
     *
     * @param array<int, string>|Undefined $connectRedirectDomains
     */
    public function update(
        string $workspaceId,
        string|Undefined $name = Undefined::Value,
        string|Undefined $type = Undefined::Value,
        string|Undefined $timezone = Undefined::Value,
        string|Undefined $language = Undefined::Value,
        string|null|Undefined $logo = Undefined::Value,
        string|null|Undefined $website = Undefined::Value,
        string|null|Undefined $country = Undefined::Value,
        string|null|Undefined $description = Undefined::Value,
        bool|Undefined $requireApproval = Undefined::Value,
        bool|Undefined $aiAltTextEnabled = Undefined::Value,
        string|null|Undefined $brandColor = Undefined::Value,
        array|Undefined $connectRedirectDomains = Undefined::Value,
    ): Workspace {
        $fields = [
            'name' => $name,
            'type' => $type,
            'timezone' => $timezone,
            'language' => $language,
            'logo' => $logo,
            'website' => $website,
            'country' => $country,
            'description' => $description,
            'requireApproval' => $requireApproval,
            'aiAltTextEnabled' => $aiAltTextEnabled,
            'brandColor' => $brandColor,
            'connectRedirectDomains' => $connectRedirectDomains,
        ];
        $body = [];
        foreach ($fields as $key => $value) {
            if (!Undefined::is($value)) {
                $body[$key] = is_array($value) ? array_values($value) : $value;
            }
        }

        return Workspace::fromArray(self::unwrap($this->http->put("/workspaces/{$workspaceId}", $body)));
    }

    /** A workspace with connected accounts answers 409 until they are disconnected or moved. */
    public function delete(string $workspaceId): void
    {
        $this->http->delete("/workspaces/{$workspaceId}");
    }
}
