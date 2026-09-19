<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use DateTimeInterface;
use Fopost\Sdk\Model\ContentBlock;
use Fopost\Sdk\Model\Delivery;
use Fopost\Sdk\Model\Page;
use Fopost\Sdk\Model\PageMeta;
use Fopost\Sdk\Model\Post;
use Fopost\Sdk\Model\SocialAccount;
use Fopost\Sdk\Undefined;
use Generator;
use InvalidArgumentException;

/** $client->posts(): create, schedule, publish, and inspect posts. */
final class PostsResource extends Resource
{
    /**
     * One page of posts. The result iterates over its items directly.
     *
     * @return Page<Post>
     */
    public function list(
        ?string $workspaceId = null,
        ?string $status = null,
        ?string $search = null,
        int $page = 1,
        int $perPage = 30,
        ?string $sort = null,
    ): Page {
        $body = $this->http->get('/posts', [
            'workspace_id' => $workspaceId,
            'status' => $status,
            'search' => $search,
            'page' => $page,
            'per_page' => $perPage,
            'sort' => $sort,
        ]);

        $items = Post::listFrom(is_array($body) && isset($body['data']) ? $body['data'] : $body);
        $rawMeta = is_array($body) && isset($body['meta']) && is_array($body['meta']) ? $body['meta'] : null;

        return new Page($items, $rawMeta !== null ? PageMeta::fromArray($rawMeta) : PageMeta::empty());
    }

    /**
     * Walk every matching post, fetching one page at a time.
     *
     * @return Generator<int, Post>
     */
    public function iterate(
        ?string $workspaceId = null,
        ?string $status = null,
        ?string $search = null,
        int $perPage = 30,
        ?string $sort = null,
        int $startPage = 1,
    ): Generator {
        foreach ($this->iteratePages($workspaceId, $status, $search, $perPage, $sort, $startPage) as $page) {
            foreach ($page->items as $post) {
                yield $post;
            }
        }
    }

    /**
     * The same walk as iterate, but yields whole pages so meta stays reachable.
     *
     * @return Generator<int, Page<Post>>
     */
    public function iteratePages(
        ?string $workspaceId = null,
        ?string $status = null,
        ?string $search = null,
        int $perPage = 30,
        ?string $sort = null,
        int $startPage = 1,
    ): Generator {
        $pageNumber = $startPage;
        while (true) {
            $page = $this->list($workspaceId, $status, $search, $pageNumber, $perPage, $sort);
            if ($page->items === []) {
                return;
            }
            yield $page;

            $lastPage = $page->meta->lastPage;
            if ($lastPage !== null && $pageNumber >= $lastPage) {
                return;
            }
            if ($lastPage === null && count($page->items) < $perPage) {
                return;
            }
            $pageNumber++;
        }
    }

    public function get(string $postId): Post
    {
        return Post::fromArray(self::unwrap($this->http->get("/posts/{$postId}")));
    }

    /**
     * Create a draft or a scheduled post.
     *
     * $status is draft or scheduled; a scheduled post needs $scheduleAt. To
     * send a post out now, create it and call publish(). $accountGroupId adds
     * that group's accounts to $accounts, so $accounts may be left empty.
     *
     * @param string|array<int, mixed>|ContentBlock $content
     * @param array<int, string|array<string, mixed>|SocialAccount> $accounts
     * @param array<int, string>|null $labels
     * @param array<string, mixed>|null $settings
     * @param array<string, mixed> $extra
     */
    public function create(
        string $workspaceId,
        string|array|ContentBlock $content,
        array $accounts = [],
        string $status = 'draft',
        string|DateTimeInterface|null $scheduleAt = null,
        ?array $labels = null,
        ?string $title = null,
        ?string $summary = null,
        ?string $contentType = null,
        ?array $settings = null,
        array $extra = [],
        ?string $accountGroupId = null,
    ): Post {
        $body = [
            'workspace_id' => $workspaceId,
            'status' => $status,
            'content' => self::normalizeContent($content),
            'accounts' => self::normalizeAccounts($accounts),
        ];

        $optional = self::compact([
            'account_group_id' => $accountGroupId,
            'schedule_at' => self::iso($scheduleAt),
            'labels' => $labels !== null ? array_values($labels) : null,
            'title' => $title,
            'summary' => $summary,
            'content_type' => $contentType,
            'settings' => $settings,
        ]);

        $body = array_merge($body, $optional, $extra);

        return Post::fromArray(self::unwrap($this->http->post('/posts', $body)));
    }

    /**
     * Partial update: only the fields you pass are sent.
     *
     * @param string|array<int, mixed>|ContentBlock|Undefined $content
     * @param array<int, mixed>|Undefined $accounts
     * @param array<int, string>|Undefined $labels
     * @param array<string, mixed>|Undefined $settings
     * @param array<string, mixed> $extra
     */
    public function update(
        string $postId,
        string|array|ContentBlock|Undefined $content = Undefined::Value,
        array|Undefined $accounts = Undefined::Value,
        string|Undefined $status = Undefined::Value,
        string|DateTimeInterface|null|Undefined $scheduleAt = Undefined::Value,
        array|Undefined $labels = Undefined::Value,
        string|null|Undefined $title = Undefined::Value,
        string|null|Undefined $summary = Undefined::Value,
        string|Undefined $contentType = Undefined::Value,
        array|Undefined $settings = Undefined::Value,
        array $extra = [],
    ): Post {
        $body = [];
        if (!Undefined::is($content)) {
            /** @var string|array<int, mixed>|ContentBlock $content */
            $body['content'] = self::normalizeContent($content);
        }
        if (!Undefined::is($accounts)) {
            /** @var array<int, mixed> $accounts */
            $body['accounts'] = self::normalizeAccounts($accounts);
        }
        if (!Undefined::is($status)) {
            $body['status'] = $status;
        }
        if (!Undefined::is($scheduleAt)) {
            /** @var string|DateTimeInterface|null $scheduleAt */
            $body['schedule_at'] = self::iso($scheduleAt);
        }
        if (!Undefined::is($labels)) {
            /** @var array<int, string> $labels */
            $body['labels'] = array_values($labels);
        }
        if (!Undefined::is($title)) {
            $body['title'] = $title;
        }
        if (!Undefined::is($summary)) {
            $body['summary'] = $summary;
        }
        if (!Undefined::is($contentType)) {
            $body['content_type'] = $contentType;
        }
        if (!Undefined::is($settings)) {
            $body['settings'] = $settings;
        }
        $body = array_merge($body, $extra);

        return Post::fromArray(self::unwrap($this->http->put("/posts/{$postId}", $body)));
    }

    public function delete(string $postId): void
    {
        $this->http->delete("/posts/{$postId}");
    }

    /**
     * Queue the post for immediate delivery to its accounts.
     *
     * @return array<string, mixed>
     */
    public function publish(string $postId): array
    {
        return self::asArray(self::unwrap($this->http->post("/posts/{$postId}/publish")));
    }

    /**
     * Move the post to scheduled, at the time given.
     *
     * @return array<string, mixed>
     */
    public function schedule(string $postId, string|DateTimeInterface $scheduleAt): array
    {
        return self::asArray(self::unwrap($this->http->post(
            "/posts/{$postId}/schedule",
            ['schedule_at' => self::iso($scheduleAt)],
        )));
    }

    /**
     * Take the post back off the schedule, returning it to draft.
     *
     * @return array<string, mixed>
     */
    public function unschedule(string $postId): array
    {
        return self::asArray(self::unwrap($this->http->post("/posts/{$postId}/unschedule")));
    }

    /** @return array<string, mixed> */
    public function cancel(string $postId): array
    {
        return self::asArray(self::unwrap($this->http->post("/posts/{$postId}/cancel")));
    }

    /**
     * Retry the deliveries that failed, leaving the successful ones alone.
     *
     * @return array<string, mixed>
     */
    public function retry(string $postId): array
    {
        return self::asArray(self::unwrap($this->http->post("/posts/{$postId}/retry")));
    }

    /**
     * Per account blockers and advisory content signals, without publishing.
     *
     * @return array<string, mixed>
     */
    public function preflight(string $postId): array
    {
        return self::asArray(self::unwrap($this->http->post("/posts/{$postId}/preflight")));
    }

    /** @return array<int, Delivery> */
    public function deliveries(string $postId): array
    {
        return Delivery::listFrom(self::unwrap($this->http->get("/posts/{$postId}/deliveries")));
    }

    /**
     * Accept a bare string, one block, or a sequence of blocks.
     *
     * @param string|array<int|string, mixed>|ContentBlock $content
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeContent(string|array|ContentBlock $content): array
    {
        if (is_string($content) || $content instanceof ContentBlock) {
            $blocks = [$content];
        } elseif ($content !== [] && !array_is_list($content)) {
            $blocks = [$content];
        } else {
            $blocks = array_values($content);
        }

        $out = [];
        foreach ($blocks as $block) {
            if (is_string($block)) {
                $out[] = ['text' => $block];
            } elseif ($block instanceof ContentBlock) {
                $media = [];
                foreach ($block->media as $item) {
                    $media[] = array_filter($item->toArray(), static fn (mixed $v): bool => $v !== null);
                }
                $out[] = ['text' => $block->text, 'media' => $media];
            } elseif (is_array($block)) {
                $out[] = array_filter($block, static fn (mixed $v): bool => $v !== null);
            } else {
                throw new InvalidArgumentException(
                    'fopost: a content block must be a string, an array, or a ContentBlock',
                );
            }
        }

        return $out;
    }

    /**
     * The API takes bare account ids; also accept account objects or ["id" => ...].
     *
     * @param array<int, mixed> $accounts
     * @return array<int, string>
     */
    private static function normalizeAccounts(array $accounts): array
    {
        $out = [];
        foreach ($accounts as $account) {
            if (is_string($account)) {
                $out[] = $account;
            } elseif ($account instanceof SocialAccount) {
                $out[] = $account->id;
            } elseif (is_array($account) && isset($account['id']) && is_string($account['id'])) {
                $out[] = $account['id'];
            } else {
                throw new InvalidArgumentException('fopost: cannot read an account id from the value given');
            }
        }

        return $out;
    }
}
