<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\KnowledgeMatch;
use Fopost\Sdk\Model\KnowledgeSource;
use Fopost\Sdk\Undefined;

/**
 * $client->knowledge(): what the workspace has told FoPost about itself.
 *
 * Retrieval over these sources is what grounds a drafted inbox reply in your
 * own answers instead of an invented one. Needs the `inbox` scope.
 */
final class KnowledgeResource extends Resource
{
    /** @return array<int, KnowledgeSource> */
    public function list(?string $workspaceId = null): array
    {
        return KnowledgeSource::listFrom(
            self::unwrap($this->http->get('/knowledge/sources', ['workspace_id' => $workspaceId])),
        );
    }

    /**
     * Add a source and queue it for indexing, so it comes back `pending`.
     *
     * `$kind` is `faq`, `text`, `url` or `file`. An `faq` or `text` source needs
     * `$content`, a `url` source needs `$url`, and a `file` source needs
     * `$mediaId` pointing at a plain-text or CSV item in the same workspace.
     */
    public function create(
        string $kind,
        string $title,
        ?string $content = null,
        ?string $url = null,
        ?string $mediaId = null,
        ?string $brandVoiceId = null,
        ?string $workspaceId = null,
    ): KnowledgeSource {
        $body = self::compact([
            'kind' => $kind,
            'title' => $title,
            'content' => $content,
            'url' => $url,
            'media_id' => $mediaId,
            'brand_voice_id' => $brandVoiceId,
            'workspace_id' => $workspaceId,
        ]);

        return KnowledgeSource::fromArray(
            self::unwrap($this->http->post('/knowledge/sources', $body)),
        );
    }

    /**
     * Partial update: only the fields you pass are sent. Changing the content
     * or the URL returns the source to `pending` and re-indexes it.
     */
    public function update(
        string $sourceId,
        string|Undefined $title = Undefined::Value,
        string|Undefined $content = Undefined::Value,
        string|Undefined $url = Undefined::Value,
        string|null|Undefined $brandVoiceId = Undefined::Value,
    ): KnowledgeSource {
        $body = [];
        if (!Undefined::is($title)) {
            $body['title'] = $title;
        }
        if (!Undefined::is($content)) {
            $body['content'] = $content;
        }
        if (!Undefined::is($url)) {
            $body['url'] = $url;
        }
        if (!Undefined::is($brandVoiceId)) {
            $body['brand_voice_id'] = $brandVoiceId;
        }

        return KnowledgeSource::fromArray(
            self::unwrap($this->http->patch("/knowledge/sources/{$sourceId}", $body)),
        );
    }

    /** Removes the source and every passage indexed from it. */
    public function delete(string $sourceId): void
    {
        $this->http->delete("/knowledge/sources/{$sourceId}");
    }

    /** Read the source again — a `url` source is re-fetched. Returns once queued. */
    public function sync(string $sourceId): void
    {
        $this->http->post("/knowledge/sources/{$sourceId}/sync", []);
    }

    /**
     * The passages closest to a question, best first. An empty array is the
     * honest answer when nothing stored answers it.
     *
     * @return array<int, KnowledgeMatch>
     */
    public function search(
        string $q,
        ?int $topK = null,
        ?string $brandVoiceId = null,
        ?string $workspaceId = null,
    ): array {
        return KnowledgeMatch::listFrom(self::unwrap($this->http->get('/knowledge/search', [
            'q' => $q,
            'top_k' => $topK,
            'brand_voice_id' => $brandVoiceId,
            'workspace_id' => $workspaceId,
        ])));
    }
}
