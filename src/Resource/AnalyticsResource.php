<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\AnalyticsOverview;
use Fopost\Sdk\Model\AnalyticsTimeSeries;
use Fopost\Sdk\Model\AudienceDemographics;
use Fopost\Sdk\Model\CollectPostResult;
use Fopost\Sdk\Model\ContentDecayReport;
use Fopost\Sdk\Model\InboxAnalytics;
use Fopost\Sdk\Model\LabelAnalytics;
use Fopost\Sdk\Model\MetricChangePage;
use Fopost\Sdk\Model\NativePost;
use Fopost\Sdk\Model\Page;
use Fopost\Sdk\Model\PostTimeline;
use Fopost\Sdk\Model\PostingFrequencyReport;
use Fopost\Sdk\Model\PostingStreakDay;
use Fopost\Sdk\Model\PostsTablePage;
use Fopost\Sdk\Model\TopPost;

/**
 * $client->analytics(): the dashboard's analytics, and the deeper readings beneath them.
 *
 * The overview, time series, top posts, labels, posts table, streak,
 * demographics and inbox calls answer what the dashboard shows. `decay`,
 * `frequency`, `timeline` and `changes` are derived from the repeated readings
 * the platform collector takes of every post as it ages. Needs the
 * `analytics` scope.
 */
final class AnalyticsResource extends Resource
{
    /** Totals, deltas and per-account follower history for the period (`$days` defaults to 30). */
    public function overview(
        ?int $days = null,
        ?string $workspaceId = null,
        ?string $accountId = null,
    ): AnalyticsOverview {
        $query = self::compact(['days' => $days, 'workspace_id' => $workspaceId, 'account_id' => $accountId]);

        return AnalyticsOverview::fromArray(self::unwrap($this->http->get('/analytics/overview', $query)));
    }

    /** Daily totals. Give `$from` and `$to` (YYYY-MM-DD) for an exact range instead of `$days`. */
    public function timeSeries(
        ?int $days = null,
        ?string $from = null,
        ?string $to = null,
        ?string $workspaceId = null,
        ?string $accountId = null,
    ): AnalyticsTimeSeries {
        $query = self::compact([
            'days' => $days,
            'from' => $from,
            'to' => $to,
            'workspace_id' => $workspaceId,
            'account_id' => $accountId,
        ]);

        return AnalyticsTimeSeries::fromArray(self::unwrap($this->http->get('/analytics/time-series', $query)));
    }

    /**
     * The posts that earned the most. `$sort` picks the metric; `$label` keeps one label's posts.
     *
     * @return array<int, TopPost>
     */
    public function topPosts(
        ?int $limit = null,
        ?int $days = null,
        ?string $from = null,
        ?string $to = null,
        ?string $sort = null,
        ?string $label = null,
        ?string $workspaceId = null,
        ?string $accountId = null,
    ): array {
        $query = self::compact([
            'limit' => $limit,
            'days' => $days,
            'from' => $from,
            'to' => $to,
            'sort' => $sort,
            'label' => $label,
            'workspace_id' => $workspaceId,
            'account_id' => $accountId,
        ]);

        return TopPost::listFrom(self::unwrap($this->http->get('/analytics/top-posts', $query)));
    }

    /**
     * What each label's posts earned, so a campaign can be read as a whole.
     *
     * @return array<int, LabelAnalytics>
     */
    public function labels(
        ?int $days = null,
        ?string $from = null,
        ?string $to = null,
        ?string $workspaceId = null,
        ?string $accountId = null,
    ): array {
        $query = self::compact([
            'days' => $days,
            'from' => $from,
            'to' => $to,
            'workspace_id' => $workspaceId,
            'account_id' => $accountId,
        ]);

        return LabelAnalytics::listFrom(self::unwrap($this->http->get('/analytics/labels', $query)));
    }

    /** Posts with where each delivery stands, one page at a time, plus status counts for the whole set. */
    public function postsTable(
        int $page = 1,
        int $perPage = 20,
        ?int $days = null,
        ?string $from = null,
        ?string $to = null,
        ?string $workspaceId = null,
        ?string $accountId = null,
    ): PostsTablePage {
        $query = self::compact([
            'page' => $page,
            'per_page' => $perPage,
            'days' => $days,
            'from' => $from,
            'to' => $to,
            'workspace_id' => $workspaceId,
            'account_id' => $accountId,
        ]);

        return PostsTablePage::fromArray($this->http->get('/analytics/posts-table', $query));
    }

    /**
     * How many posts went out each day.
     *
     * @return array<int, PostingStreakDay>
     */
    public function postingStreak(?string $workspaceId = null): array
    {
        $query = self::compact(['workspace_id' => $workspaceId]);
        $body = self::asArray($this->http->get('/analytics/posting-streak', $query));

        return PostingStreakDay::listFrom($body['streak'] ?? []);
    }

    /** Who the audience is. `$audience` is followers, engaged or reached. */
    public function demographics(
        ?string $audience = null,
        ?string $workspaceId = null,
        ?string $accountId = null,
    ): AudienceDemographics {
        $query = self::compact(['audience' => $audience, 'workspace_id' => $workspaceId, 'account_id' => $accountId]);

        return AudienceDemographics::fromArray(self::unwrap($this->http->get('/analytics/demographics', $query)));
    }

    /** Inbox volume and response times. `$tz` is the IANA zone the heatmap and series are bucketed in. */
    public function inbox(
        ?int $days = null,
        ?string $tz = null,
        ?string $workspaceId = null,
        ?string $accountId = null,
    ): InboxAnalytics {
        $query = self::compact([
            'days' => $days,
            'tz' => $tz,
            'workspace_id' => $workspaceId,
            'account_id' => $accountId,
        ]);

        return InboxAnalytics::fromArray(self::unwrap($this->http->get('/analytics/inbox', $query)));
    }

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
