<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\WhatsappBlockResult;
use Fopost\Sdk\Model\WhatsappCommerceSettings;
use Fopost\Sdk\Model\WhatsappEncryptionKeyStatus;
use Fopost\Sdk\Model\WhatsappFlow;
use Fopost\Sdk\Model\WhatsappFlowJsonResult;
use Fopost\Sdk\Model\WhatsappFlowResponse;
use Fopost\Sdk\Model\WhatsappGroup;
use Fopost\Sdk\Model\WhatsappProfile;
use Fopost\Sdk\Model\WhatsappSandboxSession;
use Fopost\Sdk\Model\WhatsappTemplate;
use Fopost\Sdk\Undefined;

/**
 * A WhatsApp Business connection.
 *
 * The platform owns templates, flows, the profile and the commerce settings, so
 * every method here is a live read or write against the customer's own WhatsApp
 * Business Account. All of it answers 503 until WhatsApp is set up.
 */
final class WhatsappResource extends Resource
{
    // ─── Profile ───────────────────────────────────────────────────

    public function getProfile(string $accountId): WhatsappProfile
    {
        return WhatsappProfile::fromArray(
            self::unwrap($this->http->get("/accounts/{$accountId}/whatsapp/profile")),
        );
    }

    /** @param array<int, string>|Undefined $websites */
    public function updateProfile(
        string $accountId,
        string|Undefined $about = Undefined::Value,
        string|Undefined $address = Undefined::Value,
        string|Undefined $description = Undefined::Value,
        string|Undefined $vertical = Undefined::Value,
        array|Undefined $websites = Undefined::Value,
        string|Undefined $profilePictureMediaId = Undefined::Value,
    ): WhatsappProfile {
        $fields = [
            'about' => $about,
            'address' => $address,
            'description' => $description,
            'vertical' => $vertical,
            'websites' => $websites,
            'profile_picture_media_id' => $profilePictureMediaId,
        ];
        $body = [];
        foreach ($fields as $key => $value) {
            if (!Undefined::is($value)) {
                $body[$key] = $value;
            }
        }

        return WhatsappProfile::fromArray(
            self::unwrap($this->http->request(
                'PATCH',
                "/accounts/{$accountId}/whatsapp/profile",
                $body === [] ? (object) [] : $body,
            )),
        );
    }

    /** A review, not a write: the number keeps its old name until it passes. */
    public function requestDisplayName(string $accountId, string $displayName): bool
    {
        $body = self::asArray(self::unwrap($this->http->post(
            "/accounts/{$accountId}/whatsapp/profile/display-name",
            ['display_name' => $displayName],
        )));

        return ($body['requested'] ?? false) === true;
    }

    public function setUsername(string $accountId, string $username): WhatsappProfile
    {
        return WhatsappProfile::fromArray(self::unwrap($this->http->put(
            "/accounts/{$accountId}/whatsapp/profile/username",
            ['username' => $username],
        )));
    }

    // ─── Templates ─────────────────────────────────────────────────

    /** @return array<int, WhatsappTemplate> */
    public function listTemplates(string $accountId, ?string $after = null): array
    {
        return WhatsappTemplate::listFrom(self::unwrap($this->http->get(
            "/accounts/{$accountId}/whatsapp/templates",
            self::compact(['after' => $after]),
        )));
    }

    /**
     * The platform's pre-written templates, for adapting instead of drafting.
     *
     * @return array<int, mixed>
     */
    public function listTemplateLibrary(string $accountId, ?string $search = null): array
    {
        $body = self::unwrap($this->http->get(
            "/accounts/{$accountId}/whatsapp/templates/library",
            self::compact(['search' => $search]),
        ));

        return is_array($body) ? $body : [];
    }

    public function getTemplate(string $accountId, string $templateId): WhatsappTemplate
    {
        return WhatsappTemplate::fromArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/whatsapp/templates/{$templateId}"),
        ));
    }

    /**
     * Files a template for review; the result carries the status it was given.
     *
     * @param array<int, array<string, mixed>> $components
     */
    public function createTemplate(
        string $accountId,
        string $name,
        string $language,
        string $category,
        array $components,
        ?bool $allowCategoryChange = null,
    ): WhatsappTemplate {
        $body = self::compact([
            'name' => $name,
            'language' => $language,
            'category' => $category,
            'components' => $components,
            'allow_category_change' => $allowCategoryChange,
        ]);

        return WhatsappTemplate::fromArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/whatsapp/templates", $body),
        ));
    }

    /** @param array<int, array<string, mixed>>|null $libraryTemplateButtonInputs */
    public function importTemplate(
        string $accountId,
        string $libraryTemplateName,
        string $name,
        string $language,
        string $category,
        ?array $libraryTemplateButtonInputs = null,
    ): WhatsappTemplate {
        $body = self::compact([
            'library_template_name' => $libraryTemplateName,
            'name' => $name,
            'language' => $language,
            'category' => $category,
            'library_template_button_inputs' => $libraryTemplateButtonInputs,
        ]);

        return WhatsappTemplate::fromArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/whatsapp/templates/import", $body),
        ));
    }

    /** @param array<int, array<string, mixed>>|null $components */
    public function updateTemplate(
        string $accountId,
        string $templateId,
        ?string $category = null,
        ?array $components = null,
    ): WhatsappTemplate {
        $body = self::compact(['category' => $category, 'components' => $components]);

        return WhatsappTemplate::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/accounts/{$accountId}/whatsapp/templates/{$templateId}",
            $body === [] ? (object) [] : $body,
        )));
    }

    /** The name is required: it is what the platform deletes by. */
    public function deleteTemplate(string $accountId, string $templateId, string $name): bool
    {
        $query = http_build_query(['name' => $name]);
        $body = self::asArray(self::unwrap($this->http->delete(
            "/accounts/{$accountId}/whatsapp/templates/{$templateId}?{$query}",
        )));

        return ($body['deleted'] ?? true) === true;
    }

    // ─── Groups ────────────────────────────────────────────────────

    /** @return array<int, WhatsappGroup> */
    public function listGroups(string $accountId): array
    {
        return WhatsappGroup::listFrom(self::unwrap(
            $this->http->get("/accounts/{$accountId}/whatsapp/groups"),
        ));
    }

    /** Participation is invite-only: send the invite link, there is no add. */
    public function createGroup(
        string $accountId,
        string $subject,
        ?string $description = null,
    ): WhatsappGroup {
        $body = self::compact(['subject' => $subject, 'description' => $description]);

        return WhatsappGroup::fromArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/whatsapp/groups", $body),
        ));
    }

    public function getGroup(string $accountId, string $groupId): WhatsappGroup
    {
        return WhatsappGroup::fromArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/whatsapp/groups/{$groupId}"),
        ));
    }

    public function updateGroup(
        string $accountId,
        string $groupId,
        ?string $subject = null,
        ?string $description = null,
    ): WhatsappGroup {
        $body = self::compact(['subject' => $subject, 'description' => $description]);

        return WhatsappGroup::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/accounts/{$accountId}/whatsapp/groups/{$groupId}",
            $body === [] ? (object) [] : $body,
        )));
    }

    public function deleteGroup(string $accountId, string $groupId): bool
    {
        $body = self::asArray(self::unwrap(
            $this->http->delete("/accounts/{$accountId}/whatsapp/groups/{$groupId}"),
        ));

        return ($body['deleted'] ?? true) === true;
    }

    public function getGroupInviteLink(string $accountId, string $groupId): ?string
    {
        $body = self::asArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/whatsapp/groups/{$groupId}/invite-link"),
        ));
        $link = $body['inviteLink'] ?? null;

        return is_string($link) ? $link : null;
    }

    /** Issues a new link and invalidates the old one. */
    public function resetGroupInviteLink(string $accountId, string $groupId): ?string
    {
        $body = self::asArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/whatsapp/groups/{$groupId}/invite-link"),
        ));
        $link = $body['inviteLink'] ?? null;

        return is_string($link) ? $link : null;
    }

    /** @param array<int, string> $users */
    public function removeGroupParticipants(string $accountId, string $groupId, array $users): bool
    {
        $body = self::asArray(self::unwrap($this->http->delete(
            "/accounts/{$accountId}/whatsapp/groups/{$groupId}/participants",
            ['users' => $users],
        )));

        return ($body['deleted'] ?? true) === true;
    }

    // ─── Blocking ──────────────────────────────────────────────────

    /** @return array<int, string> */
    public function listBlocked(string $accountId, ?string $after = null): array
    {
        $body = self::unwrap($this->http->get(
            "/accounts/{$accountId}/whatsapp/block",
            self::compact(['after' => $after]),
        ));

        return is_array($body) ? array_values(array_filter($body, 'is_string')) : [];
    }

    /** @param array<int, string> $users */
    public function blockUsers(string $accountId, array $users): WhatsappBlockResult
    {
        return WhatsappBlockResult::fromArray(self::unwrap($this->http->post(
            "/accounts/{$accountId}/whatsapp/block",
            ['users' => $users],
        )));
    }

    /** @param array<int, string> $users */
    public function unblockUsers(string $accountId, array $users): WhatsappBlockResult
    {
        return WhatsappBlockResult::fromArray(self::unwrap($this->http->delete(
            "/accounts/{$accountId}/whatsapp/block",
            ['users' => $users],
        )));
    }

    // ─── Commerce ──────────────────────────────────────────────────

    public function getCommerceSettings(string $accountId): WhatsappCommerceSettings
    {
        return WhatsappCommerceSettings::fromArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/whatsapp/commerce"),
        ));
    }

    public function updateCommerceSettings(
        string $accountId,
        ?bool $cartEnabled = null,
        ?bool $catalogVisible = null,
    ): WhatsappCommerceSettings {
        $body = self::compact([
            'is_cart_enabled' => $cartEnabled,
            'is_catalog_visible' => $catalogVisible,
        ]);

        return WhatsappCommerceSettings::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/accounts/{$accountId}/whatsapp/commerce",
            $body === [] ? (object) [] : $body,
        )));
    }

    public function linkCatalog(string $accountId, string $catalogId): WhatsappCommerceSettings
    {
        return WhatsappCommerceSettings::fromArray(self::unwrap($this->http->post(
            "/accounts/{$accountId}/whatsapp/commerce/catalog",
            ['catalog_id' => $catalogId],
        )));
    }

    // ─── Flows ─────────────────────────────────────────────────────

    /** @return array<int, WhatsappFlow> */
    public function listFlows(string $accountId): array
    {
        return WhatsappFlow::listFrom(self::unwrap(
            $this->http->get("/accounts/{$accountId}/whatsapp/flows"),
        ));
    }

    public function getFlow(string $accountId, string $flowId): WhatsappFlow
    {
        return WhatsappFlow::fromArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/whatsapp/flows/{$flowId}"),
        ));
    }

    /** @param array<int, string> $categories */
    public function createFlow(
        string $accountId,
        string $name,
        array $categories,
        ?string $endpointUri = null,
        ?string $cloneFlowId = null,
    ): WhatsappFlow {
        $body = self::compact([
            'name' => $name,
            'categories' => $categories,
            'endpoint_uri' => $endpointUri,
            'clone_flow_id' => $cloneFlowId,
        ]);

        return WhatsappFlow::fromArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/whatsapp/flows", $body),
        ));
    }

    /** @param array<int, string>|null $categories */
    public function updateFlow(
        string $accountId,
        string $flowId,
        ?string $name = null,
        ?array $categories = null,
        ?string $endpointUri = null,
    ): WhatsappFlow {
        $body = self::compact([
            'name' => $name,
            'categories' => $categories,
            'endpoint_uri' => $endpointUri,
        ]);

        return WhatsappFlow::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/accounts/{$accountId}/whatsapp/flows/{$flowId}",
            $body === [] ? (object) [] : $body,
        )));
    }

    /** Drafts only; a published flow is deprecated instead. */
    public function deleteFlow(string $accountId, string $flowId): bool
    {
        $body = self::asArray(self::unwrap(
            $this->http->delete("/accounts/{$accountId}/whatsapp/flows/{$flowId}"),
        ));

        return ($body['deleted'] ?? true) === true;
    }

    /**
     * The platform answers with its validation errors rather than refusing.
     *
     * @param array<string, mixed> $flowJson
     */
    public function uploadFlowJson(
        string $accountId,
        string $flowId,
        array $flowJson,
    ): WhatsappFlowJsonResult {
        return WhatsappFlowJsonResult::fromArray(self::unwrap($this->http->put(
            "/accounts/{$accountId}/whatsapp/flows/{$flowId}/json",
            ['flow_json' => $flowJson],
        )));
    }

    public function publishFlow(string $accountId, string $flowId): WhatsappFlow
    {
        return WhatsappFlow::fromArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/whatsapp/flows/{$flowId}/publish"),
        ));
    }

    public function deprecateFlow(string $accountId, string $flowId): WhatsappFlow
    {
        return WhatsappFlow::fromArray(self::unwrap(
            $this->http->post("/accounts/{$accountId}/whatsapp/flows/{$flowId}/deprecate"),
        ));
    }

    /** @return array<int, WhatsappFlowResponse> */
    public function listFlowResponses(string $accountId): array
    {
        return WhatsappFlowResponse::listFrom(self::unwrap(
            $this->http->get("/accounts/{$accountId}/whatsapp/flows/responses"),
        ));
    }

    public function getEncryptionKeyStatus(string $accountId): WhatsappEncryptionKeyStatus
    {
        return WhatsappEncryptionKeyStatus::fromArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/whatsapp/flows/encryption-key"),
        ));
    }

    /** The public half only; the private half stays with the customer. */
    public function setEncryptionKey(
        string $accountId,
        string $businessPublicKey,
    ): WhatsappEncryptionKeyStatus {
        return WhatsappEncryptionKeyStatus::fromArray(self::unwrap($this->http->put(
            "/accounts/{$accountId}/whatsapp/flows/encryption-key",
            ['business_public_key' => $businessPublicKey],
        )));
    }

    // ─── Account state and sandbox ─────────────────────────────────

    /** @return array<string, mixed> */
    public function getAccountEvents(string $accountId): array
    {
        return self::asArray(self::unwrap(
            $this->http->get("/accounts/{$accountId}/whatsapp/events"),
        ));
    }

    /** @return array<int, WhatsappSandboxSession> */
    public function listSandboxSessions(string $workspaceId): array
    {
        return WhatsappSandboxSession::listFrom(self::unwrap($this->http->get(
            '/whatsapp/sandbox/sessions',
            ['workspaceId' => $workspaceId],
        )));
    }

    /** Sends a template from the platform-owned test number; needs the publish scope. */
    public function createSandboxSession(
        string $workspaceId,
        string $phoneNumber,
    ): WhatsappSandboxSession {
        return WhatsappSandboxSession::fromArray(self::unwrap($this->http->post(
            '/whatsapp/sandbox/sessions',
            ['workspaceId' => $workspaceId, 'phoneNumber' => $phoneNumber],
        )));
    }
}
