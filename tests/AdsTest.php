<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class AdsTest extends TestCase
{
    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private static function ad(array $overrides = []): array
    {
        return $overrides + [
            'id' => 'ad_1',
            'kind' => 'boost',
            'name' => 'Boost',
            'goal' => 'traffic',
            'status' => 'paused',
        ];
    }

    public function testListParsesAnAdWithItsInsights(): void
    {
        $this->transport->push(200, ['data' => [[
            'id' => 'ad_1',
            'workspaceId' => 'w_1',
            'kind' => 'boost',
            'name' => 'Launch week boost',
            'goal' => 'engagement',
            'status' => 'paused',
            'effectiveStatus' => 'PAUSED',
            'connectionId' => 'conn_1',
            'adAccountId' => 'act_123',
            'sourcePostId' => 'p_1',
            'budgetMinor' => 2500,
            'budgetType' => 'daily',
            'currency' => 'USD',
            'targeting' => ['countries' => ['US'], 'ageMin' => 18],
            'insights' => ['impressions' => 1200, 'reach' => 900, 'clicks' => 30, 'spendMinor' => 1250],
            'insightsAt' => '2026-09-18T10:00:00Z',
            'createdAt' => '2026-09-17T10:00:00Z',
        ]]]);

        $ads = $this->client()->ads()->list('w_1');

        $this->assertSame('GET', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/ads?workspace_id=w_1', $this->transport->last()['url']);
        $this->assertSame('boost', $ads[0]->kind);
        $this->assertSame(2500, $ads[0]->budgetMinor);
        $this->assertSame(['US'], $ads[0]->targeting['countries']);
        $this->assertSame(1250, $ads[0]->insights?->spendMinor);
        $this->assertSame('2026-09-18', $ads[0]->insightsAt?->format('Y-m-d'));
    }

    public function testReadOnlyListsTakeTheWorkspaceParam(): void
    {
        $this->transport->push(200, ['data' => [
            ['id' => '9001', 'name' => 'Spring sale', 'campaignName' => 'Spring'],
        ]]);
        $external = $this->client()->ads()->external('w_1');
        $this->assertSame('https://api.fopost.com/v1/ads/external?workspace_id=w_1', $this->transport->last()['url']);
        $this->assertSame('Spring', $external[0]->campaignName);

        $this->transport->push(200, ['data' => [[
            'id' => 'p_1',
            'text' => 'We shipped',
            'deliveries' => [['accountId' => 'acc_1', 'platform' => 'facebook', 'username' => 'yourbrand']],
        ]]]);
        $boostable = $this->client()->ads()->boostable('w_1');
        $this->assertSame('https://api.fopost.com/v1/ads/boostable?workspace_id=w_1', $this->transport->last()['url']);
        $this->assertSame('facebook', $boostable[0]->deliveries[0]['platform']);

        $this->transport->push(200, ['data' => [
            ['id' => 'conn_1', 'provider' => 'meta', 'authType' => 'business', 'name' => 'Your Brand'],
        ]]);
        $connections = $this->client()->ads()->connections('w_1');
        $this->assertSame(
            'https://api.fopost.com/v1/ads/connections?workspace_id=w_1',
            $this->transport->last()['url'],
        );
        $this->assertSame('business', $connections[0]->authType);

        $this->transport->push(200, ['data' => [[
            'connectionId' => 'conn_1',
            'name' => 'Your Brand',
            'adAccounts' => [['id' => 'act_123', 'name' => 'Main', 'currency' => 'USD', 'status' => 1]],
            'pages' => [['id' => '555', 'name' => 'Your Brand', 'instagramUserId' => null]],
        ]]]);
        $sources = $this->client()->ads()->sources('w_1');
        $this->assertSame('https://api.fopost.com/v1/ads/sources?workspace_id=w_1', $this->transport->last()['url']);
        $this->assertSame('act_123', $sources[0]->adAccounts[0]['id']);
        $this->assertSame('555', $sources[0]->pages[0]['id']);
    }

    public function testConnectionsAreAuthorizedAndDeleted(): void
    {
        $this->transport->push(200, ['data' => ['url' => 'https://www.facebook.com/dialog/oauth?state=abc']]);

        $url = $this->client()->ads()->authorizeMeta('w_1', returnTo: '/ads');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/ads/connections/meta/authorize', $this->transport->last()['url']);
        $this->assertSame(['workspaceId' => 'w_1', 'returnTo' => '/ads'], $this->transport->lastJson());
        $this->assertSame('https://www.facebook.com/dialog/oauth?state=abc', $url);

        $this->transport->push(200, ['message' => 'deleted']);
        $this->client()->ads()->deleteConnection('conn_1', 'w_1');
        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/ads/connections/conn_1?workspace_id=w_1',
            $this->transport->last()['url'],
        );
        $this->assertNull($this->transport->last()['body']);
    }

    public function testBoostPostsACamelCaseBody(): void
    {
        $this->transport->push(201, ['data' => self::ad()]);

        $ad = $this->client()->ads()->boost(
            workspaceId: 'w_1',
            connectionId: 'conn_1',
            adAccountId: 'act_123',
            postId: 'p_1',
            accountId: 'acc_1',
            name: 'Boost',
            goal: 'traffic',
            budget: ['minor' => 2500, 'type' => 'daily'],
            targeting: ['countries' => ['US']],
        );

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/ads/boost', $this->transport->last()['url']);
        $this->assertSame([
            'workspaceId' => 'w_1',
            'connectionId' => 'conn_1',
            'adAccountId' => 'act_123',
            'postId' => 'p_1',
            'accountId' => 'acc_1',
            'name' => 'Boost',
            'goal' => 'traffic',
            'budget' => ['minor' => 2500, 'type' => 'daily'],
            'targeting' => ['countries' => ['US']],
        ], $this->transport->lastJson());
        $this->assertSame('paused', $ad->status);
    }

    public function testCreateSendsTheCreativeAndThePausedFlag(): void
    {
        $this->transport->push(201, ['data' => self::ad(['id' => 'ad_2', 'kind' => 'ad', 'status' => 'active'])]);

        $ad = $this->client()->ads()->create(
            workspaceId: 'w_1',
            connectionId: 'conn_1',
            adAccountId: 'act_123',
            pageId: '555',
            name: 'Ad',
            goal: 'awareness',
            budget: ['minor' => 10000, 'type' => 'lifetime', 'endAt' => '2026-10-01T00:00:00Z'],
            targeting: ['countries' => ['US'], 'gender' => 'all'],
            text: 'Meet the new plan',
            destinationUrl: 'https://yourbrand.com/plans',
            paused: false,
        );

        $this->assertSame('https://api.fopost.com/v1/ads', $this->transport->last()['url']);
        $body = $this->transport->lastJson();
        $this->assertSame('555', $body['pageId']);
        $this->assertSame('Meet the new plan', $body['text']);
        $this->assertSame('https://yourbrand.com/plans', $body['destinationUrl']);
        $this->assertFalse($body['paused']);
        $this->assertArrayNotHasKey('headline', $body);
        $this->assertSame('ad', $ad->kind);
    }

    public function testRefreshStatusAndDeleteCarryTheWorkspaceInTheQuery(): void
    {
        $this->transport->push(200, ['data' => self::ad(['status' => 'active', 'effectiveStatus' => 'ACTIVE'])]);
        $ad = $this->client()->ads()->refresh('ad_1', 'w_1');
        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/ads/ad_1/refresh?workspace_id=w_1',
            $this->transport->last()['url'],
        );
        $this->assertSame('ACTIVE', $ad->effectiveStatus);

        $this->transport->push(200, ['data' => self::ad()]);
        $ad = $this->client()->ads()->setStatus('ad_1', 'w_1', 'paused');
        $this->assertSame('PATCH', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/ads/ad_1?workspace_id=w_1', $this->transport->last()['url']);
        $this->assertSame(['status' => 'paused'], $this->transport->lastJson());
        $this->assertSame('paused', $ad->status);

        $this->transport->push(200, ['message' => 'deleted']);
        $this->client()->ads()->delete('ad_1', 'w_1');
        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/ads/ad_1?workspace_id=w_1', $this->transport->last()['url']);
    }

    public function testAudiencesAndTargeting(): void
    {
        $this->transport->push(200, ['data' => [
            'audiences' => [['id' => 'aud_1', 'name' => 'Newsletter', 'subtype' => 'CUSTOM', 'sizeLower' => 1000]],
            'pixels' => [['id' => 'px_1', 'name' => 'Site']],
            'workspaceId' => 'w_1',
        ]]);
        $result = $this->client()->ads()->audiences('conn_1', 'act_123');
        $this->assertSame(
            'https://api.fopost.com/v1/ads/audiences?connection_id=conn_1&ad_account_id=act_123',
            $this->transport->last()['url'],
        );
        $this->assertSame('Newsletter', $result->audiences[0]->name);
        $this->assertSame(1000, $result->audiences[0]->sizeLower);
        $this->assertSame('px_1', $result->pixels[0]['id']);

        $this->transport->push(201, ['data' => ['id' => 'aud_2', 'added' => 2]]);
        $created = $this->client()->ads()->createAudience(
            'w_1',
            'conn_1',
            'act_123',
            'Lookalike',
            ['subtype' => 'LOOKALIKE', 'originAudienceId' => 'aud_1', 'country' => 'US'],
        );
        $this->assertSame('https://api.fopost.com/v1/ads/audiences', $this->transport->last()['url']);
        $this->assertSame([
            'workspaceId' => 'w_1',
            'connectionId' => 'conn_1',
            'adAccountId' => 'act_123',
            'name' => 'Lookalike',
            'spec' => ['subtype' => 'LOOKALIKE', 'originAudienceId' => 'aud_1', 'country' => 'US'],
        ], $this->transport->lastJson());
        $this->assertSame('aud_2', $created->id);
        $this->assertSame(2, $created->added);

        $this->transport->push(200, ['data' => [['id' => '6003', 'name' => 'Coffee', 'detail' => 'Interest']]]);
        $options = $this->client()->ads()->searchTargeting('conn_1', 'interest', 'coff');
        $this->assertSame(
            'https://api.fopost.com/v1/ads/targeting/search?connection_id=conn_1&type=interest&q=coff',
            $this->transport->last()['url'],
        );
        $this->assertSame('Coffee', $options[0]->name);
    }

    public function testLeadFormsAreListedCreatedAndRead(): void
    {
        $this->transport->push(200, ['data' => [[
            'connectionId' => 'conn_1',
            'connectionName' => 'Your Brand',
            'pageId' => '555',
            'forms' => [
                [
                    'id' => 'f_1',
                    'name' => 'Demo request',
                    'status' => 'ACTIVE',
                    'leadsCount' => 12,
                    'questions' => ['EMAIL'],
                ],
            ],
        ]]]);
        $sources = $this->client()->ads()->leadForms('w_1');
        $this->assertSame('https://api.fopost.com/v1/ads/lead-forms?workspace_id=w_1', $this->transport->last()['url']);
        $this->assertSame(12, $sources[0]->forms[0]->leadsCount);
        $this->assertSame(['EMAIL'], $sources[0]->forms[0]->questions);

        $this->transport->push(201, ['data' => ['id' => 'f_2']]);
        $formId = $this->client()->ads()->createLeadForm(
            workspaceId: 'w_1',
            connectionId: 'conn_1',
            pageId: '555',
            name: 'Newsletter',
            questions: ['EMAIL', 'FULL_NAME'],
            privacyPolicyUrl: 'https://yourbrand.com/privacy',
            thankYouMessage: 'Thanks, talk soon',
        );
        $this->assertSame('https://api.fopost.com/v1/ads/lead-forms', $this->transport->last()['url']);
        $this->assertSame([
            'workspaceId' => 'w_1',
            'connectionId' => 'conn_1',
            'pageId' => '555',
            'name' => 'Newsletter',
            'questions' => ['EMAIL', 'FULL_NAME'],
            'privacyPolicyUrl' => 'https://yourbrand.com/privacy',
            'thankYouMessage' => 'Thanks, talk soon',
        ], $this->transport->lastJson());
        $this->assertSame('f_2', $formId);

        $this->transport->push(200, ['data' => [
            'leads' => [
                [
                    'id' => 'l_1',
                    'fields' => [['name' => 'full_name', 'values' => ['Sam Okafor']]],
                    'isOrganic' => false,
                ],
            ],
            'nextCursor' => 'cur_2',
        ]]);
        $leads = $this->client()->ads()->leads('f_2', 'conn_1', '555', after: 'cur_1');
        $this->assertSame(
            'https://api.fopost.com/v1/ads/lead-forms/f_2/leads?connection_id=conn_1&page_id=555&after=cur_1',
            $this->transport->last()['url'],
        );
        $this->assertSame('l_1', $leads->leads[0]->id);
        $this->assertSame('full_name', $leads->leads[0]->fields[0]['name']);
        $this->assertSame('cur_2', $leads->nextCursor);
    }
}
