<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\RemoteArticle;
use Fopost\Sdk\Model\RemoteBlog;
use Fopost\Sdk\Model\RemoteProduct;
use Fopost\Sdk\Undefined;

/**
 * $client->blogs(): content a connected site already owns.
 *
 * Most of this SDK creates content. These calls reach what is already there:
 * the articles on a WordPress site or a Shopify store's blog, and a Shopify
 * store's products. Every id here is the platform's own, never a FoPost id.
 *
 * Reads need the `posts` scope. Anything that changes the site needs `posts`
 * and `publish`, because a change here is visible to the site's own readers.
 */
final class BlogsResource extends Resource
{
    /** @return array<int, RemoteBlog> */
    public function listBlogs(string $accountId): array
    {
        return RemoteBlog::listFrom(self::unwrap($this->http->get("/accounts/{$accountId}/blogs")));
    }

    /**
     * Articles on the blog, newest first, drafts included.
     *
     * $status is published, draft, pending or scheduled; $q matches the title.
     *
     * @return array<int, RemoteArticle>
     */
    public function listArticles(
        string $accountId,
        string $blogId,
        ?int $limit = null,
        ?string $status = null,
        ?string $q = null,
    ): array {
        $params = self::compact(['limit' => $limit, 'status' => $status, 'q' => $q]);

        return RemoteArticle::listFrom(
            self::unwrap($this->http->get("/accounts/{$accountId}/blogs/{$blogId}/articles", $params)),
        );
    }

    public function getArticle(string $accountId, string $blogId, string $articleId): RemoteArticle
    {
        return RemoteArticle::fromArray(
            self::unwrap($this->http->get("/accounts/{$accountId}/blogs/{$blogId}/articles/{$articleId}")),
        );
    }

    /**
     * Write a new article to the blog. Needs the `publish` scope.
     *
     * $body is FoPost body markup; the site's own format is rendered from it.
     *
     * @param array<int, string>|null $tags
     */
    public function createArticle(
        string $accountId,
        string $blogId,
        string $title,
        string $body,
        ?string $excerpt = null,
        ?string $status = null,
        ?array $tags = null,
        ?string $authorName = null,
        ?string $imageUrl = null,
    ): RemoteArticle {
        $payload = self::compact([
            'title' => $title,
            'body' => $body,
            'excerpt' => $excerpt,
            'status' => $status,
            'tags' => $tags === null ? null : array_values($tags),
            'author_name' => $authorName,
            'image_url' => $imageUrl,
        ]);

        return RemoteArticle::fromArray(
            self::unwrap($this->http->post("/accounts/{$accountId}/blogs/{$blogId}/articles", $payload)),
        );
    }

    /**
     * Change the live article in place. Needs the `publish` scope.
     *
     * Only the arguments you pass are touched, and the article is addressed by
     * its own id, so an edit never creates a second post on the site. Pass at
     * least one field.
     *
     * @param array<int, string>|Undefined $tags
     */
    public function updateArticle(
        string $accountId,
        string $blogId,
        string $articleId,
        string|Undefined $title = Undefined::Value,
        string|Undefined $body = Undefined::Value,
        string|Undefined $excerpt = Undefined::Value,
        string|Undefined $status = Undefined::Value,
        array|Undefined $tags = Undefined::Value,
        string|Undefined $authorName = Undefined::Value,
        string|Undefined $imageUrl = Undefined::Value,
    ): RemoteArticle {
        $payload = self::defined([
            'title' => $title,
            'body' => $body,
            'excerpt' => $excerpt,
            'status' => $status,
            'tags' => $tags,
            'author_name' => $authorName,
            'image_url' => $imageUrl,
        ]);

        return RemoteArticle::fromArray(
            self::unwrap($this->http->request(
                'PATCH',
                "/accounts/{$accountId}/blogs/{$blogId}/articles/{$articleId}",
                $payload,
            )),
        );
    }

    /** Remove the article from the site. Needs `publish`; this cannot be undone. */
    public function deleteArticle(string $accountId, string $blogId, string $articleId): void
    {
        $this->http->delete("/accounts/{$accountId}/blogs/{$blogId}/articles/{$articleId}");
    }

    /**
     * The store's products. $status is active, draft or archived.
     *
     * @return array<int, RemoteProduct>
     */
    public function listProducts(
        string $accountId,
        ?int $limit = null,
        ?string $status = null,
        ?string $q = null,
    ): array {
        $params = self::compact(['limit' => $limit, 'status' => $status, 'q' => $q]);

        return RemoteProduct::listFrom(
            self::unwrap($this->http->get("/accounts/{$accountId}/products", $params)),
        );
    }

    /**
     * Change a product on the store. Needs the `publish` scope.
     *
     * Only what you pass changes; pass at least one field.
     *
     * @param array<int, string>|Undefined $tags
     */
    public function updateProduct(
        string $accountId,
        string $productId,
        string|Undefined $title = Undefined::Value,
        string|Undefined $description = Undefined::Value,
        string|Undefined $status = Undefined::Value,
        array|Undefined $tags = Undefined::Value,
        string|Undefined $productType = Undefined::Value,
        string|Undefined $vendor = Undefined::Value,
    ): RemoteProduct {
        $payload = self::defined([
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'tags' => $tags,
            'product_type' => $productType,
            'vendor' => $vendor,
        ]);

        return RemoteProduct::fromArray(
            self::unwrap($this->http->request('PATCH', "/accounts/{$accountId}/products/{$productId}", $payload)),
        );
    }

    /**
     * Drop the arguments the caller never passed, so a PATCH stays partial and
     * an omitted field keeps whatever the site already had.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private static function defined(array $body): array
    {
        return array_filter($body, static fn (mixed $v): bool => !Undefined::is($v));
    }
}
