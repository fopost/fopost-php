<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\BlogArticle;
use Fopost\Sdk\Model\BlogArticleFull;
use Fopost\Sdk\Model\BlogKeyword;
use Fopost\Sdk\Model\BlogSettings;
use Fopost\Sdk\Model\BlogSettingsUpdate;
use Fopost\Sdk\Model\BlogWebsiteTest;
use Fopost\Sdk\Undefined;

/**
 * $client->blog(): planned keywords and the articles written from them, for
 * a website rather than a social network.
 *
 * Reads, settings and keyword edits need the `posts` scope. Anything that
 * spends credits (planKeywords, requestArticle, regenerateArticle) needs `ai`
 * and answers 402 (PaymentRequiredException) on an empty balance.
 * publishArticle needs `publish`. Every call names the workspace: as a query
 * parameter on GET, PATCH and DELETE routes with an id, in the body elsewhere.
 * Request and response fields are camelCase.
 */
final class BlogResource extends Resource
{
    public function getSettings(string $workspaceId): BlogSettings
    {
        return BlogSettings::fromArray(
            self::unwrap($this->http->get('/blog/settings', ['workspaceId' => $workspaceId])),
        );
    }

    /**
     * Partial update; only the keys you pass are sent (camelCase, as the API
     * spells them: siteUrl, topic, cadencePerWeek, targetKind, websiteUrl, ...).
     *
     * Setting websiteUrl for the first time issues a signing secret, returned
     * once on the result's websiteSecret. The product_blog target is admin only.
     *
     * @param array<string, mixed> $patch
     */
    public function updateSettings(string $workspaceId, array $patch): BlogSettingsUpdate
    {
        $body = ['workspaceId' => $workspaceId] + $patch;

        return BlogSettingsUpdate::fromArray(self::asArray($this->http->patch('/blog/settings', $body)));
    }

    /** Issue a new website signing secret. It is returned once and never readable again. */
    public function rotateWebsiteSecret(string $workspaceId): string
    {
        $body = self::asArray(self::unwrap(
            $this->http->post('/blog/settings/website-secret', ['workspaceId' => $workspaceId]),
        ));

        return (string) ($body['websiteSecret'] ?? '');
    }

    /**
     * Send a signed article.test delivery to the website. A site that refused
     * it answers 502, which arrives as an ApiException carrying the reason.
     */
    public function testWebsite(string $workspaceId): BlogWebsiteTest
    {
        return BlogWebsiteTest::fromArray(self::unwrap(
            $this->http->post('/blog/settings/website-test', ['workspaceId' => $workspaceId]),
        ));
    }

    /**
     * @param string|array<int, string>|null $status one status, or several
     * @return array<int, BlogKeyword>
     */
    public function listKeywords(string $workspaceId, string|array|null $status = null, ?int $limit = null): array
    {
        $body = $this->http->get('/blog/keywords', self::compact([
            'workspaceId' => $workspaceId,
            'status' => is_array($status) ? implode(',', $status) : $status,
            'limit' => $limit,
        ]));

        return BlogKeyword::listFrom(self::unwrap($body));
    }

    /**
     * Queue a keyword research run. Spends credits and answers 202; the
     * keywords arrive as `suggested` rows on listKeywords().
     *
     * @param array<int, string>|null $seeds
     */
    public function planKeywords(string $workspaceId, ?array $seeds = null): void
    {
        $this->http->post('/blog/keywords/plan', self::compact([
            'workspaceId' => $workspaceId,
            'seeds' => $seeds,
        ]));
    }

    /** @param string|null $plannedFor YYYY-MM-DD */
    public function addKeyword(string $workspaceId, string $keyword, ?string $plannedFor = null): BlogKeyword
    {
        return BlogKeyword::fromArray(self::unwrap($this->http->post('/blog/keywords', self::compact([
            'workspaceId' => $workspaceId,
            'keyword' => $keyword,
            'plannedFor' => $plannedFor,
        ]))));
    }

    /** Partial update: pass null for plannedFor to clear it, omit it to leave it alone. */
    public function updateKeyword(
        string $workspaceId,
        string $keywordId,
        string|Undefined $status = Undefined::Value,
        string|null|Undefined $plannedFor = Undefined::Value,
    ): BlogKeyword {
        $body = [];
        if (!Undefined::is($status)) {
            $body['status'] = $status;
        }
        if (!Undefined::is($plannedFor)) {
            $body['plannedFor'] = $plannedFor;
        }

        return BlogKeyword::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/blog/keywords/{$keywordId}",
            $body === [] ? (object) [] : $body,
            ['workspaceId' => $workspaceId],
        )));
    }

    public function deleteKeyword(string $workspaceId, string $keywordId): void
    {
        $this->http->request('DELETE', "/blog/keywords/{$keywordId}", null, ['workspaceId' => $workspaceId]);
    }

    /**
     * @param string|array<int, string>|null $status one status, or several
     * @return array<int, BlogArticle>
     */
    public function listArticles(string $workspaceId, string|array|null $status = null, ?int $limit = null): array
    {
        $body = $this->http->get('/blog/articles', self::compact([
            'workspaceId' => $workspaceId,
            'status' => is_array($status) ? implode(',', $status) : $status,
            'limit' => $limit,
        ]));

        return BlogArticle::listFrom(self::unwrap($body));
    }

    /**
     * Queue an article. Give one of $title, $keyword or $keywordId (a row from
     * the plan; its term becomes the target). Spends credits; the article comes
     * back `queued` and turns into a draft when the writer finishes.
     */
    public function requestArticle(
        string $workspaceId,
        ?string $title = null,
        ?string $keyword = null,
        ?string $keywordId = null,
        ?string $instructions = null,
    ): BlogArticle {
        return BlogArticle::fromArray(self::unwrap($this->http->post('/blog/articles', self::compact([
            'workspaceId' => $workspaceId,
            'title' => $title,
            'keyword' => $keyword,
            'keywordId' => $keywordId,
            'instructions' => $instructions,
        ]))));
    }

    public function getArticle(string $workspaceId, string $articleId): BlogArticleFull
    {
        return BlogArticleFull::fromArray(self::unwrap(
            $this->http->get("/blog/articles/{$articleId}", ['workspaceId' => $workspaceId]),
        ));
    }

    /**
     * Partial update of a draft or scheduled article; only the keys you pass
     * are sent (title, slug, excerpt, metaTitle, metaDescription, bodyMarkdown,
     * tags, publishAt). publishAt puts the draft on the calendar, null returns
     * it to review. An article that is not editable answers 409 invalid_state.
     *
     * @param array<string, mixed> $patch
     */
    public function updateArticle(string $workspaceId, string $articleId, array $patch): BlogArticleFull
    {
        return BlogArticleFull::fromArray(self::unwrap($this->http->request(
            'PATCH',
            "/blog/articles/{$articleId}",
            $patch === [] ? (object) [] : $patch,
            ['workspaceId' => $workspaceId],
        )));
    }

    /** Write it again from the same brief. Spends credits. */
    public function regenerateArticle(string $workspaceId, string $articleId): BlogArticle
    {
        return $this->articleAction($workspaceId, $articleId, 'regenerate');
    }

    /** Needs the publish scope. Answers 409 no_target when the blog has nowhere to publish. */
    public function publishArticle(string $workspaceId, string $articleId): BlogArticle
    {
        return $this->articleAction($workspaceId, $articleId, 'publish');
    }

    public function rejectArticle(string $workspaceId, string $articleId): BlogArticle
    {
        return $this->articleAction($workspaceId, $articleId, 'reject');
    }

    public function deleteArticle(string $workspaceId, string $articleId): void
    {
        $this->http->request('DELETE', "/blog/articles/{$articleId}", null, ['workspaceId' => $workspaceId]);
    }

    private function articleAction(string $workspaceId, string $articleId, string $action): BlogArticle
    {
        return BlogArticle::fromArray(self::unwrap($this->http->request(
            'POST',
            "/blog/articles/{$articleId}/{$action}",
            null,
            ['workspaceId' => $workspaceId],
        )));
    }
}
