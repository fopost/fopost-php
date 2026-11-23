<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class GoogleAdsTest extends TestCase
{
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
