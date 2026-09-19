<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\ApiException;

final class RedditTest extends TestCase
{
    public function testSubredditsRulesAndFlairs(): void
    {
        $this->transport->push(200, ['data' => [[
            'name' => 'webdev',
            'title' => 'Web Development',
            'subscribers' => 2000000,
            'over18' => false,
            'canPost' => true,
            'flairEnabled' => true,
            'iconUrl' => null,
            'isDefault' => true,
        ]]]);
        $subreddits = $this->client()->accounts()->listRedditSubreddits('a_1');
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a_1/reddit/subreddits',
            $this->transport->last()['url'],
        );
        $this->assertSame('webdev', $subreddits[0]->name);
        $this->assertTrue($subreddits[0]->isDefault);

        $this->transport->push(200, ['data' => [
            'subreddit' => 'webdev',
            'rules' => [['name' => 'No self promotion', 'description' => 'Keep it useful', 'appliesTo' => 'link']],
        ]]);
        $rules = $this->client()->accounts()->listRedditSubredditRules('a_1', 'webdev');
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a_1/reddit/subreddits/webdev/rules',
            $this->transport->last()['url'],
        );
        $this->assertSame('link', $rules->rules[0]->appliesTo);

        $this->transport->push(200, ['data' => [
            'subreddit' => 'webdev',
            'flairs' => [['id' => 'flair-1', 'text' => 'Showoff Saturday', 'editable' => false]],
        ]]);
        $flairs = $this->client()->accounts()->listRedditFlairs('a_1', 'webdev');
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a_1/reddit/flairs?subreddit=webdev',
            $this->transport->last()['url'],
        );
        $this->assertSame('flair-1', $flairs->flairs[0]->id);
    }

    public function testDefaultSubredditAcceptsNull(): void
    {
        $this->transport->push(200, ['data' => ['subreddit' => null]]);
        $result = $this->client()->accounts()->setRedditDefaultSubreddit('a_1', null);

        $this->assertSame('PUT', $this->transport->last()['method']);
        $this->assertSame(['subreddit' => null], $this->transport->lastJson());
        $this->assertNull($result->subreddit);
    }

    public function testValidateSubreddit(): void
    {
        $this->transport->push(200, ['data' => [
            'subreddit' => 'webdev',
            'exists' => true,
            'can_post' => true,
            'over_18' => false,
            'flair_enabled' => true,
            'ok' => true,
        ]]);
        $check = $this->client()->validate()->subreddit('a_1', 'webdev');

        $this->assertSame(
            'https://api.fopost.com/v1/validate/subreddit?account_id=a_1&name=webdev',
            $this->transport->last()['url'],
        );
        $this->assertTrue($check->ok);
    }

    public function testVoteSendsTheDirection(): void
    {
        $this->transport->push(200, ['data' => [
            'id' => 'i_1',
            'platform' => 'reddit',
            'type' => 'comment',
            'state' => 'unread',
            'vote' => 'down',
            'canVote' => true,
        ]]);
        $item = $this->client()->inbox()->vote('i_1', 'down');

        $this->assertSame('https://api.fopost.com/v1/inbox/i_1/vote', $this->transport->last()['url']);
        $this->assertSame(['direction' => 'down'], $this->transport->lastJson());
        $this->assertSame('down', $item->vote);
        $this->assertTrue($item->canVote);
    }

    public function testAStaleGrantIsAnApiException(): void
    {
        $this->transport->push(409, ['error' => 'reconnect_required', 'message' => 'Reconnect this account']);

        try {
            $this->client()->accounts()->listRedditSubreddits('a_1');
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            $this->assertSame(409, $e->getStatus());
            $this->assertSame('reconnect_required', $e->errorCode);
        }
    }
}
