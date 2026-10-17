<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\ApiException;

final class GoogleBusinessTest extends TestCase
{
    private const BASE = 'https://api.fopost.com/v1/accounts/a_1/gbp';

    public function testEveryMethodMapsOntoItsRoute(): void
    {
        $client = $this->client();
        $gb = $client->googleBusiness();

        $calls = [
            [fn () => $gb->getLocation('a_1'), 'GET', self::BASE . '/location'],
            [fn () => $gb->updateLocation('a_1', title: 'Corner Bakery'), 'PATCH', self::BASE . '/location'],
            [fn () => $gb->getAttributes('a_1'), 'GET', self::BASE . '/attributes'],
            [fn () => $gb->updateAttributes('a_1', []), 'PATCH', self::BASE . '/attributes'],
            [fn () => $gb->getMenus('a_1'), 'GET', self::BASE . '/menus'],
            [fn () => $gb->replaceMenus('a_1', []), 'PUT', self::BASE . '/menus'],
            [fn () => $gb->getServices('a_1'), 'GET', self::BASE . '/services'],
            [fn () => $gb->replaceServices('a_1', []), 'PUT', self::BASE . '/services'],
            [fn () => $gb->listMedia('a_1'), 'GET', self::BASE . '/media'],
            [fn () => $gb->addMedia('a_1', 'm_1'), 'POST', self::BASE . '/media'],
            [fn () => $gb->deleteMedia('a_1', 'CAoSL'), 'DELETE', self::BASE . '/media/CAoSL'],
            [fn () => $gb->listPlaceActions('a_1'), 'GET', self::BASE . '/place-actions'],
            [
                fn () => $gb->createPlaceAction('a_1', 'https://example.com/book', 'APPOINTMENT'),
                'POST',
                self::BASE . '/place-actions',
            ],
            [
                fn () => $gb->updatePlaceAction('a_1', 'links-1', isPreferred: true),
                'PATCH',
                self::BASE . '/place-actions/links-1',
            ],
            [fn () => $gb->deletePlaceAction('a_1', 'links-1'), 'DELETE', self::BASE . '/place-actions/links-1'],
            [fn () => $gb->getVerificationOptions('a_1'), 'GET', self::BASE . '/verification'],
            [fn () => $gb->startVerification('a_1', 'SMS'), 'POST', self::BASE . '/verification/start'],
            [fn () => $gb->completeVerification('a_1', 'v1', '123456'), 'POST', self::BASE . '/verification/complete'],
        ];

        foreach ($calls as [$call, $method, $url]) {
            $this->transport->push(200, ['data' => ['ok' => true]]);
            $call();
            $this->assertSame($method, $this->transport->last()['method']);
            $this->assertSame($url, $this->transport->last()['url']);
        }
    }

    public function testPatchesCarryOnlyTheFieldsTheCallerSet(): void
    {
        $this->transport->push(200, ['data' => []]);
        $this->client()->googleBusiness()->updateLocation('a_1', description: null, storeCode: 'S-12');

        $this->assertSame(['description' => null, 'store_code' => 'S-12'], $this->transport->lastJson());
    }

    public function testAPhotoIsNamedByItsLibraryId(): void
    {
        $this->transport->push(200, ['data' => []]);
        $this->client()->googleBusiness()->addMedia('a_1', 'm_1', 'INTERIOR', 'Front counter');

        $this->assertSame(
            ['media_id' => 'm_1', 'category' => 'INTERIOR', 'description' => 'Front counter'],
            $this->transport->lastJson(),
        );
    }

    public function testPerformanceRepeatsTheMetricParameter(): void
    {
        $this->transport->push(200, ['data' => []]);
        $this->client()->googleBusiness()->getPerformance(
            'a_1',
            '2026-09-01',
            '2026-09-07',
            ['CALL_CLICKS', 'WEBSITE_CLICKS'],
        );

        $this->assertSame(
            self::BASE . '/performance?start_date=2026-09-01&end_date=2026-09-07'
                . '&daily_metrics=CALL_CLICKS&daily_metrics=WEBSITE_CLICKS',
            $this->transport->last()['url'],
        );
    }

    public function testSearchKeywordsAsksTheSameRouteForTheMonthlyTerms(): void
    {
        $this->transport->push(200, ['data' => []]);
        $this->client()->googleBusiness()->getSearchKeywords('a_1', '2026-08-01', '2026-09-01');

        $this->assertStringContainsString('keywords=true', $this->transport->last()['url']);
    }

    public function testAssignHandsTheLocationToAnotherWorkspace(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'a_1', 'workspace_id' => 'w_2']]);
        $moved = $this->client()->googleBusiness()->assign('a_1', 'w_2');

        $this->assertSame(self::BASE . '/assign', $this->transport->last()['url']);
        $this->assertSame(['workspace_id' => 'w_2'], $this->transport->lastJson());
        $this->assertSame('a_1', $moved->id);
    }

    public function testAPendingApiGrantSurfacesAs503(): void
    {
        $this->transport->push(503, ['error' => 'configuration_error', 'message' => 'Not available yet']);

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(503);
        $this->client()->googleBusiness()->getLocation('a_1');
    }
}
