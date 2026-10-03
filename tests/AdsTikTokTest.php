<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class AdsTikTokTest extends TestCase
{
    public function testIdentitiesAndSparkPostsReadTheRightPaths(): void
    {
        $this->transport->push(200, ['data' => [['id' => 'bc1', 'name' => 'Brand HQ', 'role' => 'ADMIN']]]);
        $centers = $this->client()->ads()->tiktokBusinessCenters('conn_1', 'w_1');
        $this->assertSame('Brand HQ', $centers[0]->name);
        $this->assertSame(
            'https://api.fopost.com/v1/ads/tiktok/business-centers?workspace_id=w_1&connection_id=conn_1',
            $this->transport->last()['url'],
        );

        $this->transport->push(200, ['data' => [
            ['id' => 'idt_1', 'type' => 'CUSTOMIZED_USER', 'name' => 'Your Brand'],
        ]]);
        $identities = $this->client()->ads()->tiktokIdentities('conn_1', '7011', 'w_1');
        $this->assertSame('CUSTOMIZED_USER', $identities[0]->type);

        $this->transport->push(200, ['data' => [
            ['id' => 'item_99', 'identityId' => 'idt_1', 'views' => 48213],
        ]]);
        $posts = $this->client()->ads()->sparkPosts('conn_1', '7011', 'idt_1', 'w_1');
        $this->assertSame(48213, $posts[0]->views);
        $this->assertStringContainsString('identity_id=idt_1', $this->transport->last()['url']);
    }

    public function testSparkPostIdAndSmartPlusTravelInTheBody(): void
    {
        $this->transport->push(201, ['data' => ['id' => 'ad_1', 'workspaceId' => 'w_1', 'kind' => 'ad', 'name' => 'Spark', 'goal' => 'traffic', 'status' => 'paused']]);
        $this->client()->ads()->create(
            'w_1',
            'conn_1',
            '7011',
            'idt_1',
            'Spark',
            'traffic',
            ['minor' => 2000, 'type' => 'daily'],
            ['countries' => ['US'], 'ageMin' => 18, 'ageMax' => 44, 'gender' => 'all'],
            '',
            sparkPostId: 'item_99',
        );
        $this->assertSame('item_99', $this->transport->lastJson()['sparkPostId']);

        $this->transport->push(201, ['data' => ['id' => 'c1', 'name' => 'Smart', 'status' => 'PAUSED']]);
        $this->client()->ads()->createCampaign('w_1', 'conn_1', '7011', 'Smart', 'traffic', smartPlus: true);
        $this->assertTrue($this->transport->lastJson()['smartPlus']);
    }

    public function testConversionsReportWhatTheNetworkAccepted(): void
    {
        $this->transport->push(202, ['data' => ['accepted' => 2]]);

        $accepted = $this->client()->ads()->uploadConversions('w_1', 'conn_1', '7011', 'px_1', [
            ['eventName' => 'CompletePayment', 'occurredAt' => '2026-09-18T10:04:00Z'],
            ['eventName' => 'CompletePayment', 'occurredAt' => '2026-09-18T11:04:00Z'],
        ]);

        $this->assertSame(2, $accepted);
        $this->assertSame('https://api.fopost.com/v1/ads/conversions', $this->transport->last()['url']);
        $this->assertSame('px_1', $this->transport->lastJson()['pixelId']);
    }

    public function testCommentsPageAndTheThreeWrites(): void
    {
        $this->transport->push(200, ['data' => [
            'comments' => [['id' => 'cm_1', 'text' => 'nice', 'likes' => 3, 'hidden' => true]],
            'nextCursor' => '2',
        ]]);
        $page = $this->client()->ads()->comments('conn_1', 'ad_1', null, 'w_1');
        $this->assertSame('2', $page->nextCursor);
        $this->assertTrue($page->comments[0]->hidden);
        $this->assertSame(3, $page->comments[0]->likes);

        $this->transport->push(201, ['data' => ['replyId' => 'cm_2']]);
        $this->assertSame('cm_2', $this->client()->ads()->replyToComment('cm_1', 'w_1', 'conn_1', 'ad_1', 'Friday!'));
        $this->assertSame('ad_1', $this->transport->lastJson()['adId']);

        $this->transport->push(200, ['message' => 'Comment hidden']);
        $this->client()->ads()->setCommentHidden('cm_1', 'w_1', 'conn_1', 'ad_1', true);
        $this->assertTrue($this->transport->lastJson()['hidden']);

        $this->transport->push(200, ['message' => 'Comment deleted']);
        $this->client()->ads()->deleteComment('cm_1', 'w_1', 'conn_1', 'ad_1');
        // The ad travels in the body, because the path already carries the comment.
        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('ad_1', $this->transport->lastJson()['adId']);
    }
}
