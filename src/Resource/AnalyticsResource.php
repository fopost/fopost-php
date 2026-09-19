<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\CollectPostResult;
use Fopost\Sdk\Model\ContentDecayReport;
use Fopost\Sdk\Model\MetricChangePage;
use Fopost\Sdk\Model\NativePost;
use Fopost\Sdk\Model\Page;
use Fopost\Sdk\Model\PostTimeline;
use Fopost\Sdk\Model\PostingFrequencyReport;

/**
 * $client->analytics(): deeper posting analytics.
 *
 * Derived from the repeated readings the platform collector takes of every
 * post as it ages. Needs the `analytics` scope.
 */
final class AnalyticsResource extends Resource
{
    /**
     * How engagement accumulates with a post's age, and where the half-life falls.
     *
     * `$days` selects posts by publish time, not reading time.
     */
    public function decay(
        ?int $days = null,
        ?string $workspaceId = null,
        ?string $accountId = null,
    ): ContentDecayReport {
        $query = self::compact(['days' => $days, 'workspace_id' => $workspaceId, 'accountId' => $accountId]);

        return ContentDecayReport::fromArray(self::unwrap($this->http->get('/analytics/decay', $query)));
    }

    /** Weekly posting cadence set against what each cadence earned per post. */
    public function frequency(
        ?int $days = null,
        ?string $workspaceId = null,
        ?string $accountId = null,
    ): PostingFrequencyReport {
        $query = self::compact(['days' => $days, 'workspace_id' => $workspaceId, 'accountId' => $accountId]);

        return PostingFrequencyReport::fromArray(self::unwrap($this->http->get('/analytics/frequency', $query)));
    }

    /**
     * Every reading held for one post, oldest first, one timeline per delivery.
     *
     * `$idOrPermalink` is a FoPost post id or the permalink of a post made
     * natively on the network.
     */
    public function timeline(string $idOrPermalink): PostTimeline
    {
        $path = '/analytics/posts/' . rawurlencode($idOrPermalink) . '/timeline';

        return PostTimeline::fromArray(self::unwrap($this->http->get($path)));
    }

    /**
     * Readings recorded after `$since`, oldest first, with a cursor to continue.
     *
     * Poll this to mirror the metrics into your own store. Omitting `$since`
     * gives the last seven days.
     */
    public function changes(
        ?string $since = null,
        ?int $limit = null,
        ?string $workspaceId = null,
        ?string $accountId = null,
    ): MetricChangePage {
        $query = self::compact([
            'since' => $since,
            'limit' => $limit,
            'workspace_id' => $workspaceId,
            'accountId' => $accountId,
        ]);

        return MetricChangePage::fromArray(self::unwrap($this->http->get('/analytics/changes', $query)));
    }

    /**
     * Re-read one post from the network now.
     *
     * Spends the same per-user budget as a full collection run, so a burst
     * answers 429 with `retryAfter`.
     */
    public function collectPost(string $idOrPermalink): CollectPostResult
    {
        $path = '/posts/' . rawurlencode($idOrPermalink) . '/analytics/collect';

        return CollectPostResult::fromArray(self::unwrap($this->http->post($path)));
    }

    /**
     * Posts on the account that never went out through FoPost, newest first.
     *
     * @return Page<NativePost>
     */
    public function nativePosts(string $accountId, int $page = 1, int $perPage = 20, ?int $days = null): Page
    {
        $query = self::compact(['page' => $page, 'per_page' => $perPage, 'days' => $days]);

        return self::page(NativePost::class, $this->http->get("/accounts/{$accountId}/native-posts", $query));
    }
}
