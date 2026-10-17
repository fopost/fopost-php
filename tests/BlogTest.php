<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\ApiException;
use Fopost\Sdk\Exception\PaymentRequiredException;

final class BlogTest extends TestCase
{
    /** @return array<string, mixed> */
    private function settingsFixture(): array
    {
        return [
            'siteUrl' => 'https://yourbrand.com',
            'topic' => 'Garden tools',
            'audience' => null,
            'goal' => 'leads',
            'focusTopics' => 'Pruning and tool care',
            'avoidTopics' => null,
            'language' => 'en',
            'locationCode' => 2840,
            'cadencePerWeek' => 2,
            'reviewHoldDays' => 3,
            'imageTier' => 'standard',
            'inBodyImages' => 2,
            'minWords' => 900,
            'maxWords' => 1500,
            'brandVoiceId' => null,
            'targetKind' => 'website',
            'targetAccountId' => null,
            'targetPlatform' => null,
            'websiteUrl' => 'https://yourbrand.com/fopost/articles',
            'hasWebsiteSecret' => true,
            'planRun' => [
                'status' => 'done',
                'error' => null,
                'added' => 12,
                'finishedAt' => '2026-10-10T09:00:00.000Z',
            ],
            'keywordDataAvailable' => false,
            'updatedAt' => '2026-10-10T08:00:00.000Z',
        ];
    }

    /** @return array<string, mixed> */
    private function articleFixture(): array
    {
        return [
            'id' => 'art_1',
            'title' => 'How to sharpen shears',
            'slug' => 'how-to-sharpen-shears',
            'keyword' => 'sharpen garden shears',
            'excerpt' => null,
            'metaTitle' => null,
            'metaDescription' => null,
            'featuredImage' => ['mediaId' => 'med_1', 'url' => 'https://cdn.example/1.jpg', 'alt' => 'Shears'],
            'tags' => ['tools', 'care'],
            'wordCount' => 1200,
            'status' => 'draft',
            'origin' => 'user',
            'reviewUntil' => '2026-10-13T08:00:00.000Z',
            'publishAt' => null,
            'publishedAt' => null,
            'publishedUrl' => null,
            'error' => null,
            'createdAt' => '2026-10-10T08:00:00.000Z',
            'updatedAt' => '2026-10-10T08:30:00.000Z',
        ];
    }

    public function testGetSettingsPutsTheWorkspaceOnTheQuery(): void
    {
        $this->transport->push(200, ['data' => $this->settingsFixture()]);

        $settings = $this->client()->blog()->getSettings('w_1');

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertStringContainsString('/blog/settings?workspaceId=w_1', $this->transport->last()['url']);
        $this->assertSame(2, $settings->cadencePerWeek);
        $this->assertSame('website', $settings->targetKind);
        $this->assertTrue($settings->hasWebsiteSecret);
        $this->assertSame(2840, $settings->locationCode);
        $this->assertSame('leads', $settings->goal);
        $this->assertSame('Pruning and tool care', $settings->focusTopics);
        $this->assertNull($settings->avoidTopics);
        $this->assertSame('done', $settings->planRun?->status);
        $this->assertSame(12, $settings->planRun?->added);
    }

    public function testUpdateSettingsSendsTheWorkspaceInTheBodyAndReadsTheSecret(): void
    {
        $this->transport->push(200, ['data' => $this->settingsFixture(), 'websiteSecret' => 'whsec_abc']);

        $result = $this->client()->blog()->updateSettings('w_1', [
            'websiteUrl' => 'https://yourbrand.com/fopost/articles',
            'cadencePerWeek' => 2,
        ]);

        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertStringEndsWith('/blog/settings', $this->transport->last()['url']);
        $this->assertSame(
            ['workspaceId' => 'w_1', 'websiteUrl' => 'https://yourbrand.com/fopost/articles', 'cadencePerWeek' => 2],
            $this->transport->lastJson(),
        );
        $this->assertSame('whsec_abc', $result->websiteSecret);
        $this->assertSame('https://yourbrand.com', $result->settings->siteUrl);

        $this->transport->push(200, ['data' => $this->settingsFixture(), 'websiteSecret' => null]);
        $this->assertNull($this->client()->blog()->updateSettings('w_1', ['topic' => 'Lawns'])->websiteSecret);
    }

    public function testRotateAndTestWebsitePostTheWorkspace(): void
    {
        $this->transport->push(200, ['data' => ['websiteSecret' => 'whsec_new']]);
        $secret = $this->client()->blog()->rotateWebsiteSecret('w_1');

        $this->assertStringEndsWith('/blog/settings/website-secret', $this->transport->last()['url']);
        $this->assertSame(['workspaceId' => 'w_1'], $this->transport->lastJson());
        $this->assertSame('whsec_new', $secret);

        $this->transport->push(200, ['data' => ['ok' => true, 'status' => 200, 'error' => null]]);
        $test = $this->client()->blog()->testWebsite('w_1');

        $this->assertStringEndsWith('/blog/settings/website-test', $this->transport->last()['url']);
        $this->assertTrue($test->ok);
        $this->assertSame(200, $test->status);
    }

    public function testKeywordsTravelCamelCaseWithTheWorkspaceWhereTheApiReadsIt(): void
    {
        $this->transport->push(200, ['data' => [[
            'id' => 'kw_1',
            'keyword' => 'sharpen garden shears',
            'searchVolume' => 1300,
            'difficulty' => 21,
            'cpc' => 0.4,
            'intent' => 'informational',
            'source' => 'provider',
            'status' => 'suggested',
            'plannedFor' => null,
            'fitReason' => 'Owners search this before buying a sharpener.',
            'createdAt' => '2026-10-10T08:00:00.000Z',
        ]]]);
        $keywords = $this->client()->blog()->listKeywords('w_1', ['suggested', 'planned'], 50);

        $url = $this->transport->last()['url'];
        $this->assertStringContainsString('workspaceId=w_1', $url);
        $this->assertStringContainsString('status=suggested%2Cplanned', $url);
        $this->assertStringContainsString('limit=50', $url);
        $this->assertCount(1, $keywords);
        $this->assertSame(1300, $keywords[0]->searchVolume);
        $this->assertSame(0.4, $keywords[0]->cpc);
        $this->assertSame('Owners search this before buying a sharpener.', $keywords[0]->fitReason);

        $this->transport->push(202, ['data' => ['status' => 'queued']]);
        $this->client()->blog()->planKeywords('w_1', ['garden tools']);
        $this->assertStringEndsWith('/blog/keywords/plan', $this->transport->last()['url']);
        $this->assertSame(['workspaceId' => 'w_1', 'seeds' => ['garden tools']], $this->transport->lastJson());

        $this->transport->push(201, ['data' => ['id' => 'kw_2', 'keyword' => 'lawn edging', 'source' => 'user',
            'status' => 'planned', 'plannedFor' => '2026-10-20', 'createdAt' => '2026-10-10T08:00:00.000Z']]);
        $added = $this->client()->blog()->addKeyword('w_1', 'lawn edging', '2026-10-20');
        $this->assertSame(
            ['workspaceId' => 'w_1', 'keyword' => 'lawn edging', 'plannedFor' => '2026-10-20'],
            $this->transport->lastJson(),
        );
        $this->assertSame('2026-10-20', $added->plannedFor);

        $this->transport->push(200, ['data' => ['id' => 'kw_2', 'keyword' => 'lawn edging', 'source' => 'user',
            'status' => 'dismissed', 'createdAt' => '2026-10-10T08:00:00.000Z']]);
        $updated = $this->client()->blog()->updateKeyword('w_1', 'kw_2', 'dismissed', null);
        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertStringContainsString('/blog/keywords/kw_2?workspaceId=w_1', $this->transport->last()['url']);
        $this->assertSame('{"status":"dismissed","plannedFor":null}', $this->transport->last()['body']);
        $this->assertSame('dismissed', $updated->status);

        $this->transport->push(204, null);
        $this->client()->blog()->deleteKeyword('w_1', 'kw_2');
        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertStringContainsString('/blog/keywords/kw_2?workspaceId=w_1', $this->transport->last()['url']);
    }

    public function testSuggestSeedsPostsTheWorkspaceSeedsAndPrompt(): void
    {
        $this->transport->push(200, ['seeds' => ['garden shears', 'pruning saw']]);
        $seeds = $this->client()->blog()->suggestSeeds('w_1', ['garden tools'], 'Lean towards beginners.');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertStringEndsWith('/blog/keywords/suggest', $this->transport->last()['url']);
        $this->assertSame(
            ['workspaceId' => 'w_1', 'seeds' => ['garden tools'], 'prompt' => 'Lean towards beginners.'],
            $this->transport->lastJson(),
        );
        $this->assertSame(['garden shears', 'pruning saw'], $seeds);

        $this->transport->push(200, ['seeds' => []]);
        $this->assertSame([], $this->client()->blog()->suggestSeeds('w_1'));
        $this->assertSame(['workspaceId' => 'w_1'], $this->transport->lastJson());
    }

    public function testSubmitArticleMovesItToReview(): void
    {
        $this->transport->push(200, ['data' => ['status' => 'in_review'] + $this->articleFixture()]);
        $article = $this->client()->blog()->submitArticle('w_1', 'art_1');

        $this->assertSame('POST', $this->transport->last()['method']);
        $url = $this->transport->last()['url'];
        $this->assertStringContainsString('/blog/articles/art_1/submit?workspaceId=w_1', $url);
        $this->assertNull($this->transport->last()['body']);
        $this->assertSame('in_review', $article->status);

        $this->transport->push(409, ['error' => 'invalid_state', 'message' => 'Only a draft can be submitted']);
        try {
            $this->client()->blog()->submitArticle('w_1', 'art_1');
            $this->fail('expected a 409');
        } catch (ApiException $e) {
            $this->assertSame(409, $e->getStatus());
            $this->assertSame('invalid_state', $e->getErrorCode());
        }
    }

    public function testArticlesReadTheSummaryAndTheFullBody(): void
    {
        $this->transport->push(200, ['data' => [$this->articleFixture()]]);
        $articles = $this->client()->blog()->listArticles('w_1', 'draft');

        $url = $this->transport->last()['url'];
        $this->assertStringContainsString('/blog/articles?workspaceId=w_1&status=draft', $url);
        $this->assertCount(1, $articles);
        $this->assertSame(['tools', 'care'], $articles[0]->tags);
        $this->assertSame('med_1', $articles[0]->featuredImage?->mediaId);
        $this->assertNull($articles[0]->featuredImage?->position);

        $this->transport->push(202, ['data' => ['status' => 'queued'] + $this->articleFixture()]);
        $queued = $this->client()->blog()->requestArticle('w_1', keywordId: 'kw_1', instructions: 'Short intro.');
        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame(
            ['workspaceId' => 'w_1', 'keywordId' => 'kw_1', 'instructions' => 'Short intro.'],
            $this->transport->lastJson(),
        );
        $this->assertSame('queued', $queued->status);

        $this->transport->push(200, ['data' => $this->articleFixture() + [
            'instructions' => null,
            'outline' => ['Why sharpen', 'Tools'],
            'brief' => ['sources' => 3],
            'bodyMarkdown' => '# Shears',
            'bodyHtml' => '<h1>Shears</h1>',
            'images' => [
                ['mediaId' => 'med_2', 'url' => 'https://cdn.example/2.jpg', 'alt' => 'Stone', 'position' => 1],
            ],
        ]]);
        $full = $this->client()->blog()->getArticle('w_1', 'art_1');
        $this->assertStringContainsString('/blog/articles/art_1?workspaceId=w_1', $this->transport->last()['url']);
        $this->assertSame('How to sharpen shears', $full->title);
        $this->assertSame(['Why sharpen', 'Tools'], $full->outline);
        $this->assertSame('# Shears', $full->bodyMarkdown);
        $this->assertSame(1, $full->images[0]->position);
        $this->assertSame(['sources' => 3], $full->brief);
    }

    public function testArticleWritesAndActionsPutTheWorkspaceOnTheQuery(): void
    {
        $this->transport->push(200, ['data' => $this->articleFixture() + ['bodyMarkdown' => '', 'bodyHtml' => '']]);
        $this->client()->blog()->updateArticle('w_1', 'art_1', ['title' => 'Sharper shears', 'publishAt' => null]);
        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertStringContainsString('/blog/articles/art_1?workspaceId=w_1', $this->transport->last()['url']);
        $this->assertSame('{"title":"Sharper shears","publishAt":null}', $this->transport->last()['body']);

        $actions = ['regenerate' => 'regenerateArticle', 'publish' => 'publishArticle', 'reject' => 'rejectArticle'];
        foreach ($actions as $path => $method) {
            $this->transport->push(202, ['data' => ['status' => 'publishing'] + $this->articleFixture()]);
            $article = $this->client()->blog()->{$method}('w_1', 'art_1');
            $this->assertSame('POST', $this->transport->last()['method']);
            $url = $this->transport->last()['url'];
            $this->assertStringContainsString("/blog/articles/art_1/{$path}?workspaceId=w_1", $url);
            $this->assertNull($this->transport->last()['body']);
            $this->assertSame('publishing', $article->status);
        }

        $this->transport->push(204, null);
        $this->client()->blog()->deleteArticle('w_1', 'art_1');
        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertStringContainsString('/blog/articles/art_1?workspaceId=w_1', $this->transport->last()['url']);
    }

    public function testAnEmptyBalanceAndAMissingTargetKeepTheirCodes(): void
    {
        $this->transport->push(402, [
            'error' => 'insufficient_credits',
            'message' => 'Balance is empty',
            'upgrade_url' => 'https://app.example/billing',
        ]);
        try {
            $this->client()->blog()->requestArticle('w_1', title: 'Anything');
            $this->fail('expected a 402');
        } catch (PaymentRequiredException $e) {
            $this->assertSame('insufficient_credits', $e->getErrorCode());
            $this->assertSame('https://app.example/billing', $e->getUpgradeUrl());
        }

        $this->transport->push(409, ['error' => 'no_target', 'message' => 'No target set']);
        try {
            $this->client()->blog()->publishArticle('w_1', 'art_1');
            $this->fail('expected a 409');
        } catch (ApiException $e) {
            $this->assertSame(409, $e->getStatus());
            $this->assertSame('no_target', $e->getErrorCode());
        }
    }
}
