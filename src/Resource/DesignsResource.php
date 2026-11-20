<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\Design;
use Fopost\Sdk\Model\DesignExport;
use Fopost\Sdk\Model\DesignPost;
use Fopost\Sdk\Model\DesignRevision;
use Fopost\Sdk\Model\DesignVersion;
use Fopost\Sdk\Model\SlideOutline;

/**
 * $client->designs(): stories, posts, carousels, thumbnails, cards and collages. Needs the
 * `posts` scope; `textToSlides()` spends AI and also needs `ai`.
 *
 * The design document is a plain map. Pass a `\stdClass` (or decode with objects) where an
 * empty JSON object must stay `{}` rather than `[]`.
 */
final class DesignsResource extends Resource
{
    /**
     * Designs in a workspace, newest first, without their documents. `$kind` is story, post,
     * carousel, thumbnail, card or collage.
     *
     * @return array<int, Design>
     */
    public function list(string $workspaceId, ?string $kind = null, ?int $limit = null, ?int $offset = null): array
    {
        $query = ['workspace_id' => $workspaceId, 'kind' => $kind, 'limit' => $limit, 'offset' => $offset];

        return Design::listFrom(self::unwrap($this->http->get('/designs', $query)));
    }

    /** @param array<string, mixed>|object|null $document Omitted, the kind's blank document. */
    public function create(
        string $workspaceId,
        string $kind,
        ?string $name = null,
        array|object|null $document = null,
        ?string $brandVoiceId = null,
    ): Design {
        $body = self::compact([
            'workspace_id' => $workspaceId,
            'name' => $name,
            'kind' => $kind,
            'document' => $document,
            'brand_voice_id' => $brandVoiceId,
        ]);

        return Design::fromArray(self::unwrap($this->http->post('/designs', $body)));
    }

    public function get(string $designId): Design
    {
        return Design::fromArray(self::unwrap($this->http->get("/designs/{$designId}")));
    }

    /**
     * Partial update: only the fields you pass are sent. `$createVersion` saves a version
     * of the result, returned on `->version`.
     *
     * @param array<string, mixed>|object|null $document
     */
    public function update(
        string $designId,
        ?string $name = null,
        ?string $kind = null,
        array|object|null $document = null,
        ?bool $createVersion = null,
    ): DesignRevision {
        $body = self::compact([
            'name' => $name,
            'kind' => $kind,
            'document' => $document,
            'create_version' => $createVersion,
        ]);

        return DesignRevision::fromArray(
            $this->http->patch("/designs/{$designId}", $body === [] ? (object) [] : $body),
        );
    }

    public function delete(string $designId): void
    {
        $this->http->delete("/designs/{$designId}");
    }

    /**
     * Saved versions, newest first. `$withDocument` includes each version's document.
     *
     * @return array<int, DesignVersion>
     */
    public function versions(string $designId, bool $withDocument = false): array
    {
        $query = $withDocument ? ['include' => 'document'] : null;

        return DesignVersion::listFrom(self::unwrap($this->http->get("/designs/{$designId}/versions", $query)));
    }

    /** Put a saved version's document back on the design. */
    public function restoreVersion(string $designId, string $versionId): DesignRevision
    {
        return DesignRevision::fromArray(
            $this->http->post("/designs/{$designId}/versions/{$versionId}/restore"),
        );
    }

    public function duplicate(string $designId, ?string $name = null): Design
    {
        $body = $name === null ? (object) [] : ['name' => $name];

        return Design::fromArray(self::unwrap($this->http->post("/designs/{$designId}/duplicate", $body)));
    }

    /**
     * Render pages into the media library. `$format` is png, jpg, pdf, mp4 or gif;
     * `$durationSec` (5, 10 or 15) applies to mp4 and gif.
     *
     * @param array<int, int>|null $pageIndexes Omitted, every page.
     */
    public function export(
        string $designId,
        string $format,
        ?array $pageIndexes = null,
        ?int $durationSec = null,
    ): DesignExport {
        $body = self::compact([
            'format' => $format,
            'page_indexes' => $pageIndexes,
            'duration_sec' => $durationSec,
        ]);

        return DesignExport::fromArray(self::unwrap($this->http->post("/designs/{$designId}/export", $body)));
    }

    /**
     * One copy of the design per row, each row's values filling the layers of the same name.
     * `$nameColumn` names the column that titles each copy.
     *
     * @param array<int, array<string, string>> $rows
     * @return array<int, Design>
     */
    public function bulk(string $designId, array $rows, ?string $nameColumn = null): array
    {
        $body = self::compact(['rows' => $rows, 'name_column' => $nameColumn]);

        return Design::listFrom(self::unwrap($this->http->post("/designs/{$designId}/bulk", $body)));
    }

    /**
     * Posts that used this design's exported media, with their metrics.
     *
     * @return array<int, DesignPost>
     */
    public function posts(string $designId): array
    {
        return DesignPost::listFrom(self::unwrap($this->http->get("/designs/{$designId}/posts")));
    }

    /** Split long text, or a post's text, into carousel slides. Uses AI credits. */
    public function textToSlides(
        string $workspaceId,
        ?string $text = null,
        ?string $postId = null,
        ?int $maxSlides = null,
        ?string $brandVoiceId = null,
    ): SlideOutline {
        $body = self::compact([
            'workspace_id' => $workspaceId,
            'text' => $text,
            'post_id' => $postId,
            'max_slides' => $maxSlides,
            'brand_voice_id' => $brandVoiceId,
        ]);

        return SlideOutline::fromArray(self::unwrap($this->http->post('/designs/text-to-slides', $body)));
    }

    /**
     * A design built from text or a post. `$style` is quote, post or plain (default quote);
     * `$carousel` builds a multi-page carousel. Needs `$text` or `$postId`.
     */
    public function fromPost(
        string $workspaceId,
        ?string $text = null,
        ?string $postId = null,
        ?string $style = null,
        ?string $preset = null,
        ?string $accountId = null,
        ?bool $carousel = null,
        ?string $name = null,
        ?string $brandVoiceId = null,
    ): Design {
        $body = self::compact([
            'workspace_id' => $workspaceId,
            'text' => $text,
            'post_id' => $postId,
            'style' => $style,
            'preset' => $preset,
            'account_id' => $accountId,
            'carousel' => $carousel,
            'name' => $name,
            'brand_voice_id' => $brandVoiceId,
        ]);

        return Design::fromArray(self::unwrap($this->http->post('/designs/from-post', $body)));
    }
}
