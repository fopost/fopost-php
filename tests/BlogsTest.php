<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class BlogsTest extends TestCase
{
    /** @return array<string, mixed> */
    private function article(): array
    {
        return [
            'id' => '99',
            'blog_id' => '11',
            'title' => 'Spring drop',
            'body_html' => '<p>Hello</p>',
            'excerpt' => 'A short summary',
            'status' => 'published',
            'author_name' => 'Store Owner',
            'tags' => ['news'],
            'image_url' => null,
            'url' => 'https://demo.myshopify.com/blogs/article/spring-drop',
            'published_at' => '2026-09-01T10:00:00Z',
            'updated_at' => null,
        ];
    }

    public function testListBlogsReadsEveryBlogOnTheSite(): void
    {
        $this->transport->push(200, ['data' => [
            ['id' => '11', 'title' => 'News', 'handle' => 'news', 'url' => null],
        ]]);

        $blogs = $this->client()->blogs()->listBlogs('a1');

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/accounts/a1/blogs', $this->transport->last()['url']);
        $this->assertCount(1, $blogs);
        $this->assertSame('11', $blogs[0]->id);
        $this->assertSame('News', $blogs[0]->title);
    }

    public function testListArticlesPassesTheFilters(): void
    {
        $this->transport->push(200, ['data' => [$this->article()]]);

        $articles = $this->client()->blogs()->listArticles('a1', '11', 5, 'draft', 'spring');

        $this->assertStringContainsString('limit=5', $this->transport->last()['url']);
        $this->assertStringContainsString('status=draft', $this->transport->last()['url']);
        $this->assertStringContainsString('q=spring', $this->transport->last()['url']);
        $this->assertSame('99', $articles[0]->id);
        $this->assertSame(['news'], $articles[0]->tags);
        $this->assertSame('Store Owner', $articles[0]->authorName);
    }

    public function testUpdateArticleChangesItInPlace(): void
    {
        $this->transport->push(200, ['data' => $this->article()]);

        $this->client()->blogs()->updateArticle('a1', '11', '99', title: 'Spring drop, restocked');

        // The article id is in the path, which is what stops an edit from
        // creating a second post on the site.
        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a1/blogs/11/articles/99',
            $this->transport->last()['url'],
        );
        // Only what the caller named travels, so nothing else is blanked.
        $this->assertSame(['title' => 'Spring drop, restocked'], $this->transport->lastJson());
    }

    public function testCreateArticleOmitsWhatItWasNotGiven(): void
    {
        $this->transport->push(200, ['data' => $this->article()]);

        $this->client()->blogs()->createArticle('a1', '11', 'Spring drop', 'Hello', status: 'draft');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame(
            ['title' => 'Spring drop', 'body' => 'Hello', 'status' => 'draft'],
            $this->transport->lastJson(),
        );
    }

    public function testDeleteArticleHitsTheArticleRoute(): void
    {
        $this->transport->push(204, null);

        $this->client()->blogs()->deleteArticle('a1', '11', '99');

        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a1/blogs/11/articles/99',
            $this->transport->last()['url'],
        );
    }

    public function testUpdateProductSendsOnlyWhatChanged(): void
    {
        $this->transport->push(200, ['data' => [
            'id' => '7',
            'title' => 'Mug XL',
            'handle' => 'mug',
            'status' => 'draft',
            'description' => null,
            'vendor' => null,
            'product_type' => 'Drinkware',
            'tags' => [],
            'image_url' => null,
            'url' => null,
            'price' => '12.00',
            'currency' => 'USD',
            'updated_at' => null,
        ]]);

        $product = $this->client()->blogs()->updateProduct('a1', '7', title: 'Mug XL', productType: 'Drinkware');

        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertSame(
            ['title' => 'Mug XL', 'product_type' => 'Drinkware'],
            $this->transport->lastJson(),
        );
        $this->assertSame('12.00', $product->price);
    }
}
