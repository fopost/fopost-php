<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class MetaMessagingTest extends TestCase
{
    public function testIceBreakersRoundTrip(): void
    {
        $iceBreakers = [['question' => 'What are your hours?', 'payload' => 'HOURS']];
        $this->transport->push(200, ['data' => ['ice_breakers' => $iceBreakers]]);
        $this->transport->push(200, ['data' => ['ice_breakers' => $iceBreakers]]);
        $this->transport->push(200, ['data' => ['ice_breakers' => []]]);

        $read = $this->client()->accounts()->getIceBreakers('a_1');
        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a_1/messaging/ice-breakers',
            $this->transport->last()['url'],
        );
        $this->assertSame('HOURS', $read->iceBreakers[0]->payload);

        $saved = $this->client()->accounts()->setIceBreakers('a_1', $iceBreakers);
        $this->assertSame('PUT', $this->transport->last()['method']);
        $this->assertSame(['ice_breakers' => $iceBreakers], $this->transport->lastJson());
        $this->assertSame('What are your hours?', $saved->iceBreakers[0]->question);

        $cleared = $this->client()->accounts()->deleteIceBreakers('a_1');
        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame([], $cleared->iceBreakers);
    }

    public function testPersistentMenuRoundTrip(): void
    {
        $menu = [[
            'locale' => 'default',
            'call_to_actions' => [
                ['type' => 'postback', 'title' => 'Talk to Us', 'payload' => 'HUMAN'],
                ['type' => 'web_url', 'title' => 'Shop', 'url' => 'https://example.com/shop'],
            ],
        ]];
        $this->transport->push(200, ['data' => ['persistent_menu' => $menu]]);
        $this->transport->push(200, ['data' => ['persistent_menu' => $menu]]);

        $saved = $this->client()->accounts()->setPersistentMenu('a_1', $menu);
        $this->assertSame('PUT', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a_1/messaging/persistent-menu',
            $this->transport->last()['url'],
        );
        $this->assertSame(['persistent_menu' => $menu], $this->transport->lastJson());
        $this->assertSame('HUMAN', $saved->persistentMenu[0]->callToActions[0]->payload);

        $read = $this->client()->accounts()->getPersistentMenu('a_1');
        $this->assertSame('https://example.com/shop', $read->persistentMenu[0]->callToActions[1]->url);
    }

    public function testGreetingIsSentPerLocale(): void
    {
        $greeting = [['locale' => 'default', 'text' => 'Hi! Ask us anything.']];
        $this->transport->push(200, ['data' => ['greeting' => $greeting]]);

        $saved = $this->client()->accounts()->setGreeting('a_1', [['text' => 'Hi! Ask us anything.']]);

        $this->assertSame(['greeting' => $greeting], $this->transport->lastJson());
        $this->assertSame('default', $saved->greeting[0]->locale);
    }

    public function testWebhookSubscriptionReportsAndResubscribes(): void
    {
        $this->transport->push(200, ['data' => [
            'subscribed' => false,
            'fields' => ['feed'],
            'missing_fields' => ['messages'],
        ]]);
        $this->transport->push(200, ['data' => [
            'subscribed' => true,
            'fields' => ['feed', 'messages'],
            'missing_fields' => [],
        ]]);

        $lapsed = $this->client()->accounts()->getWebhookSubscription('a_1');
        $this->assertFalse($lapsed->subscribed);
        $this->assertSame(['messages'], $lapsed->missingFields);

        $fixed = $this->client()->accounts()->resubscribeWebhook('a_1');
        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a_1/webhook-subscription',
            $this->transport->last()['url'],
        );
        $this->assertTrue($fixed->subscribed);
    }

    public function testHandoverPassesAndTakesControl(): void
    {
        $this->transport->push(200, ['data' => ['app_id' => '263902037430900', 'control' => 'passed']]);
        $this->transport->push(200, ['data' => ['app_id' => null, 'control' => 'taken']]);

        $passed = $this->client()->inbox()->handover('t_1', 'a_1', '263902037430900');
        $this->assertSame(
            'https://api.fopost.com/v1/inbox/conversations/t_1/handover',
            $this->transport->last()['url'],
        );
        $this->assertSame(
            ['account_id' => 'a_1', 'app_id' => '263902037430900'],
            $this->transport->lastJson(),
        );
        $this->assertSame('passed', $passed->control);

        $taken = $this->client()->inbox()->handover('t_1', 'a_1');
        $this->assertSame(['account_id' => 'a_1'], $this->transport->lastJson());
        $this->assertNull($taken->appId);
    }
}
