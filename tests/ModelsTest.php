<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Model\Post;
use Fopost\Sdk\Model\SocialAccount;
use Fopost\Sdk\Platforms;
use PHPUnit\Framework\TestCase as BaseTestCase;

final class ModelsTest extends BaseTestCase
{
    public function testFieldsAreReadUnderBothWireSpellings(): void
    {
        $snake = SocialAccount::fromArray(['id' => 'a_1', 'platform' => 'x', 'is_primary' => true]);
        $camel = SocialAccount::fromArray(['id' => 'a_1', 'platform' => 'x', 'isPrimary' => true]);

        $this->assertTrue($snake->isPrimary);
        $this->assertTrue($camel->isPrimary);
    }

    public function testUnknownKeysSurviveOnRaw(): void
    {
        $post = Post::fromArray(['id' => 'p_1', 'status' => 'draft', 'brand_new_field' => 42]);

        $this->assertSame(42, $post->get('brand_new_field'));
        $this->assertSame(42, $post->toArray()['brand_new_field']);
        $this->assertSame(42, json_decode((string) json_encode($post), true)['brand_new_field']);
    }

    public function testNestedModelsAreParsed(): void
    {
        $post = Post::fromArray([
            'id' => 'p_1',
            'status' => 'draft',
            'content' => [['text' => 'Hi', 'media' => [['type' => 'image', 'url' => 'https://x.test/a.png']]]],
            'accounts' => [['id' => 'a_1', 'platform' => 'bluesky']],
            'labels' => [['id' => 'l_1', 'name' => 'Launch']],
            'settings' => ['bluesky' => ['reply_to' => 'x']],
        ]);

        $this->assertSame('image', $post->content[0]->media[0]->type);
        $this->assertSame('a_1', $post->accounts[0]->id);
        $this->assertSame('Launch', $post->labels[0]->name);
        $this->assertSame(['bluesky' => ['reply_to' => 'x']], $post->settings);
    }

    public function testPlatformConstantsAreExposed(): void
    {
        $this->assertContains('bluesky', Platforms::ALL);
        $this->assertContains('instagram-business', Platforms::ALL);
        $this->assertContains('pending_approval', Platforms::POST_STATUSES);
    }
}
