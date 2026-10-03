<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class GoogleAdsTest extends TestCase
{
    public function testKeywordsNameTheConnectionAndTheCustomer(): void
    {
        $this->transport->push(200, ['data' => [[
            'id' => '1234567890~keyword~77~99',
            'adGroupId' => '1234567890~adGroup~77',
            'text' => 'running shoes',
            'matchType' => 'EXACT',
            'status' => 'ENABLED',
            'cpcBidMinor' => 180,
            'negative' => false,
        ]]]);

        $keywords = $this->client()->ads()->google()->keywords(
            'conn_1',
            '1234567890',
            'ws_1',
            '1234567890~adGroup~77',
        );

        $this->assertCount(1, $keywords);
        $this->assertSame('running shoes', $keywords[0]->text);
        $this->assertSame(180, $keywords[0]->cpcBidMinor);

        $url = $this->transport->last()['url'];
        $this->assertStringContainsString('/ads/google/keywords', $url);
        $this->assertStringContainsString('connection_id=conn_1', $url);
        $this->assertStringContainsString('customer_id=1234567890', $url);
    }

    public function testCreateKeywordSendsACamelCaseBody(): void
    {
        $this->transport->push(201, ['data' => ['id' => '1234567890~keyword~77~99']]);

        $id = $this->client()->ads()->google()->createKeyword(
            'ws_1',
            'conn_1',
            '1234567890',
            '1234567890~adGroup~77',
            'running shoes',
            'EXACT',
        );

        $this->assertSame('1234567890~keyword~77~99', $id);
        $body = $this->transport->lastJson();
        $this->assertSame('1234567890~adGroup~77', $body['adGroupId']);
        $this->assertSame('EXACT', $body['matchType']);
        $this->assertSame('1234567890', $body['customerId']);
    }

    public function testDeleteCarriesTheScopeInTheBody(): void
    {
        $this->transport->push(204, null);

        $this->client()->ads()->google()->deleteAsset('1234567890~asset~4321', 'ws_1', 'conn_1', '1234567890');

        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame([
            'workspaceId' => 'ws_1',
            'connectionId' => 'conn_1',
            'customerId' => '1234567890',
        ], $this->transport->lastJson());
    }

    public function testAdScheduleIsReplacedWithPut(): void
    {
        $this->transport->push(200, ['data' => ['slots' => 2]]);

        $slots = $this->client()->ads()->google()->setAdSchedule(
            'ws_1',
            'conn_1',
            '1234567890',
            '1234567890~campaign~55',
            [['dayOfWeek' => 'MONDAY', 'startHour' => 9, 'endHour' => 18]],
        );

        $this->assertSame(2, $slots);
        $this->assertSame('PUT', $this->transport->last()['method']);
    }

    public function testQueryReturnsRowsAsGoogleSendsThem(): void
    {
        $this->transport->push(200, ['data' => ['rows' => [['campaign' => ['id' => '55']]]]]);

        $rows = $this->client()->ads()->google()->query(
            'conn_1',
            '1234567890',
            'SELECT campaign.id FROM campaign',
        );

        $this->assertSame([['campaign' => ['id' => '55']]], $rows);
        $this->assertStringContainsString('/ads/insights/query', $this->transport->last()['url']);
    }

    public function testAuthorizeGoogleHasItsOwnRoute(): void
    {
        $this->transport->push(200, ['data' => ['url' => 'https://accounts.google.com/o/x']]);

        $url = $this->client()->ads()->authorizeGoogle('ws_1');

        $this->assertSame('https://accounts.google.com/o/x', $url);
        $this->assertStringContainsString('/ads/connections/google/authorize', $this->transport->last()['url']);
    }
}
